<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Exceptions\ConcurrentOperationException;
use App\Exceptions\InsufficientBalanceException;
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

    public function depositar(User $usuario, int $valorEmCentavos): Transaction
    {
        return $this->aplicar($usuario, TransactionType::Credit, $valorEmCentavos);
    }

    public function sacar(User $usuario, int $valorEmCentavos): Transaction
    {
        return $this->aplicar($usuario, TransactionType::Debit, $valorEmCentavos);
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
        $carteira = Wallet::where('user_id', $usuario->id)->firstOrFail();

        return $carteira->transactions()
            ->when($filtros['type'] ?? null, fn ($consulta, $tipo) => $consulta->where('type', $tipo))
            ->when($filtros['from'] ?? null, fn ($consulta, $de) => $consulta->where('created_at', '>=', Carbon::parse($de)->startOfDay()))
            ->when($filtros['to'] ?? null, fn ($consulta, $ate) => $consulta->where('created_at', '<=', Carbon::parse($ate)->endOfDay()))
            ->latest()
            ->latest('id') // desempate estável quando duas movimentações têm o mesmo instante
            ->paginate((int) ($filtros['per_page'] ?? 15));
    }

    /** Atualiza o saldo e grava a movimentação na mesma transação do banco. */
    private function aplicar(User $usuario, TransactionType $tipo, int $valorEmCentavos): Transaction
    {
        if ($valorEmCentavos <= 0) {
            throw new InvalidArgumentException('O valor deve ser maior que zero.');
        }

        try {
            return DB::transaction(function () use ($usuario, $tipo, $valorEmCentavos) {
                // Trava a linha: duas operações simultâneas não leem o mesmo saldo
                $carteira = Wallet::where('user_id', $usuario->id)->lockForUpdate()->firstOrFail();

                $novoSaldo = $tipo === TransactionType::Credit
                    ? $carteira->balance + $valorEmCentavos
                    : $carteira->balance - $valorEmCentavos;

                // Checa depois de travar: o saldo lido aqui é o definitivo
                if ($novoSaldo < 0) {
                    throw new InsufficientBalanceException;
                }

                $carteira->update(['balance' => $novoSaldo]);

                return $carteira->transactions()->create([
                    'type' => $tipo,
                    'amount' => $valorEmCentavos,
                    'balance_after' => $novoSaldo,
                ]);
            }, 3); // até 3 tentativas se o banco abortar por deadlock
        } catch (QueryException $erro) {
            // Esgotadas as tentativas: responde 409 em vez de um 500 genérico
            if ($this->causedByConcurrencyError($erro)) {
                throw new ConcurrentOperationException($erro);
            }

            throw $erro;
        }
    }
}
