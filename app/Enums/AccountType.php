<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum AccountType: string
{
    use EnumHelpers;

    case Checking = 'checking';
    case Wallet = 'wallet';
    case Brokerage = 'brokerage';
    case CreditCard = 'credit_card';

    public function label(): string
    {
        return match ($this) {
            self::Checking => 'Conta corrente',
            self::Wallet => 'Carteira (dinheiro)',
            self::Brokerage => 'Corretora',
            self::CreditCard => 'Cartão de crédito',
        };
    }

    /** Cartão exige closing_day, due_day e credit_limit. */
    public function isCreditCard(): bool
    {
        return $this === self::CreditCard;
    }

    /** Formas de pagamento que fazem sentido para este tipo de conta. */
    public function allowedPaymentMethods(): array
    {
        return match ($this) {
            self::Checking => [PaymentMethod::Pix, PaymentMethod::Debit, PaymentMethod::BankSlip, PaymentMethod::Transfer],
            self::Wallet => [PaymentMethod::Cash],
            self::Brokerage => [PaymentMethod::Pix, PaymentMethod::Transfer],
            self::CreditCard => [PaymentMethod::Credit],
        };
    }
}
