<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Exceptions\ConcurrentOperationException;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\DetectsConcurrencyErrors;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WalletService
{
    use DetectsConcurrencyErrors;

    public function depositar(User $usuario, int $valorEmCentavos): Transaction
    {
        return $this->aplicar($usuario, TransactionType::Credit, $valorEmCentavos);
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
