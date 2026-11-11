<?php

namespace Database\Seeders;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/** Usuário de demonstração, com movimentações espalhadas pelos últimos 30 dias. */
class DemoSeeder extends Seeder
{
    public const EMAIL = 'demo@wallet.speedlora.app';

    public const SENHA = 'Demo@12345';

    public function run(): void
    {
        DB::transaction(function () {
            $usuario = User::updateOrCreate(
                ['email' => self::EMAIL],
                ['name' => 'Usuário Demo', 'password' => Hash::make(self::SENHA)],
            );

            $carteira = Wallet::firstOrCreate(['user_id' => $usuario->id], ['balance' => 0]);

            // Idempotente: rodar de novo recria o histórico em vez de duplicá-lo
            $carteira->transactions()->delete();

            $saldo = 0;

            foreach ($this->movimentacoes() as $movimentacao) {
                $saldo += $movimentacao['tipo'] === TransactionType::Credit
                    ? $movimentacao['valor']
                    : -$movimentacao['valor'];

                // created_at não é preenchível em massa: libera só neste ponto
                Transaction::unguarded(fn () => $carteira->transactions()->create([
                    'type' => $movimentacao['tipo'],
                    'amount' => $movimentacao['valor'],
                    'balance_after' => $saldo,
                    'created_at' => now()->subDays($movimentacao['dias'])->setTime(10, 0),
                ]));
            }

            $carteira->update(['balance' => $saldo]);
        });
    }

    /**
     * Da mais antiga para a mais recente, valores em centavos. O saldo nunca fica negativo.
     *
     * @return list<array{dias: int, tipo: TransactionType, valor: int}>
     */
    private function movimentacoes(): array
    {
        return [
            ['dias' => 30, 'tipo' => TransactionType::Credit, 'valor' => 50000],
            ['dias' => 27, 'tipo' => TransactionType::Debit, 'valor' => 12050],
            ['dias' => 24, 'tipo' => TransactionType::Credit, 'valor' => 8000],
            ['dias' => 20, 'tipo' => TransactionType::Debit, 'valor' => 4590],
            ['dias' => 17, 'tipo' => TransactionType::Debit, 'valor' => 20000],
            ['dias' => 13, 'tipo' => TransactionType::Credit, 'valor' => 15000],
            ['dias' => 9, 'tipo' => TransactionType::Debit, 'valor' => 6325],
            ['dias' => 6, 'tipo' => TransactionType::Credit, 'valor' => 3575],
            ['dias' => 3, 'tipo' => TransactionType::Debit, 'valor' => 9000],
            ['dias' => 1, 'tipo' => TransactionType::Credit, 'valor' => 25000],
        ];
    }
}
