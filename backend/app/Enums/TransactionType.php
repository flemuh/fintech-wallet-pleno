<?php

namespace App\Enums;

/** Tipo da movimentação: crédito aumenta o saldo, débito diminui. */
enum TransactionType: string
{
    case Credit = 'credit';
    case Debit = 'debit';
}
