<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Exceptions\ConcurrentOperationException;
use App\Exceptions\IdempotencyKeyReusedException;
use App\Exceptions\InsufficientBalanceException;
use App\Models\IdempotencyKey;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\DetectsConcurrencyErrors;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WalletService
{
    use DetectsConcurrencyErrors;

    /** Fuso em que o usuário enxerga "o mês atual". */
    private const FUSO_DO_NEGOCIO = 'America/Sao_Paulo';

    public function depositar(User $usuario, int $valorEmCentavos, ?string $chave = null): Transaction
    {
        return $this->aplicar($usuario, TransactionType::Credit, $valorEmCentavos, $chave);
    }

    public function sacar(User $usuario, int $valorEmCentavos, ?string $chave = null): Transaction
    {
        return $this->aplicar($usuario, TransactionType::Debit, $valorEmCentavos, $chave);
    }

    /**
     * Lista as movimentações da carteira do usuário, da mais recente para a mais antiga.
     * Filtros aceitos: type, from, to (datas inclusivas) e per_page.
     *
     * @param  array<string, mixed>  $filtros
     * @return LengthAwarePaginator<int, Transaction>
     */
    public function historico(User $usuario, array $filtros = []): LengthAwarePaginator
    {
        return $this->carteiraDo($usuario)->transactions()
            ->when($filtros['type'] ?? null, fn ($consulta, $tipo) => $consulta->where('type', $tipo))
            ->when($filtros['from'] ?? null, fn ($consulta, $de) => $consulta
                ->where('created_at', '>=', Carbon::parse($de, self::FUSO_DO_NEGOCIO)->startOfDay()->utc()))
            ->when($filtros['to'] ?? null, fn ($consulta, $ate) => $consulta
                ->where('created_at', '<=', Carbon::parse($ate, self::FUSO_DO_NEGOCIO)->endOfDay()->utc()))
            ->latest()
            ->latest('id') // desempate estável quando duas movimentações têm o mesmo instante
            ->paginate((int) ($filtros['per_page'] ?? 15));
    }

    /**
     * Saldo atual e totais depositado e sacado no mês corrente, em centavos, numa única consulta.
     * O mês é o de São Paulo, convertido para UTC (fuso em que o banco guarda as datas).
     *
     * @return array{balance: int, month_credit: int, month_debit: int}
     */
    public function resumo(User $usuario): array
    {
        $carteira = $this->carteiraDo($usuario);

        $inicioDoMes = Carbon::now(self::FUSO_DO_NEGOCIO)->startOfMonth();
        $inicioDoProximoMes = $inicioDoMes->copy()->addMonth();

        $totais = DB::table('transactions')
            ->where('wallet_id', $carteira->id)
            ->where('created_at', '>=', $inicioDoMes->copy()->utc())
            ->where('created_at', '<', $inicioDoProximoMes->copy()->utc())
            ->selectRaw("coalesce(sum(amount) filter (where type = 'credit'), 0) as credito_mes")
            ->selectRaw("coalesce(sum(amount) filter (where type = 'debit'), 0) as debito_mes")
            ->first();

        return [
            'balance' => $carteira->balance,
            'month_credit' => (int) ($totais->credito_mes ?? 0),
            'month_debit' => (int) ($totais->debito_mes ?? 0),
        ];
    }

    /**
     * Atualiza o saldo e grava a movimentação na mesma transação do banco.
     * Com uma chave de idempotência já usada, devolve a movimentação daquela vez, sem repetir a operação.
     */
    private function aplicar(User $usuario, TransactionType $tipo, int $valorEmCentavos, ?string $chave): Transaction
    {
        if ($valorEmCentavos <= 0) {
            throw new InvalidArgumentException('O valor deve ser maior que zero.');
        }

        try {
            return DB::transaction(function () use ($usuario, $tipo, $valorEmCentavos, $chave) {
                // Trava a linha: duas operações simultâneas não leem o mesmo saldo
                $carteira = $this->carteiraDo($usuario, travar: true);

                // Com a carteira travada, duas requisições com a mesma chave passam por aqui uma de cada vez
                $hashDaOperacao = hash('sha256', $tipo->value.'|'.$valorEmCentavos);
                $repetida = $chave === null ? null : $this->operacaoJaFeita($usuario, $chave, $hashDaOperacao);
                if ($repetida !== null) {
                    return $repetida;
                }

                $novoSaldo = $tipo === TransactionType::Credit
                    ? $carteira->balance + $valorEmCentavos
                    : $carteira->balance - $valorEmCentavos;

                // Checa depois de travar: o saldo lido aqui é o definitivo
                if ($novoSaldo < 0) {
                    throw new InsufficientBalanceException;
                }

                $carteira->update(['balance' => $novoSaldo]);

                $transacao = $carteira->transactions()->create([
                    'type' => $tipo,
                    'amount' => $valorEmCentavos,
                    'balance_after' => $novoSaldo,
                ]);

                if ($chave !== null) {
                    IdempotencyKey::create([
                        'user_id' => $usuario->id,
                        'key' => $chave,
                        'request_hash' => $hashDaOperacao,
                        'transaction_id' => $transacao->id,
                    ]);
                }

                return $transacao;
            }, 3); // até 3 tentativas se o banco abortar por deadlock
        } catch (QueryException $erro) {
            // Esgotadas as tentativas: responde 409 em vez de um 500 genérico
            if ($this->causedByConcurrencyError($erro)) {
                throw new ConcurrentOperationException($erro);
            }

            throw $erro;
        }
    }

    /** Movimentação já gravada com esta chave, ou null. Chave igual com operação diferente é recusada. */
    private function operacaoJaFeita(User $usuario, string $chave, string $hashDaOperacao): ?Transaction
    {
        $registro = IdempotencyKey::where('user_id', $usuario->id)->where('key', $chave)->first();

        if ($registro === null) {
            return null;
        }

        if ($registro->request_hash !== $hashDaOperacao) {
            throw new IdempotencyKeyReusedException;
        }

        return $registro->transaction;
    }

    /** Carteira do usuário. Com $travar, a linha fica bloqueada até o fim da transação do banco. */
    private function carteiraDo(User $usuario, bool $travar = false): Wallet
    {
        $consulta = Wallet::where('user_id', $usuario->id);

        if ($travar) {
            $consulta->lockForUpdate();
        }

        return $consulta->firstOrFail();
    }
}
