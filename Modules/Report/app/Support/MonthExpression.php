<?php

namespace Modules\Report\Support;

class PaymentMonthExpression
{
    /**
     * SQL expression that renders a payment date as a `Y-m` month key.
     */
    public static function for(string $driver): string
    {
        return match ($driver) {
            'sqlite' => "strftime('%Y-%m', payment_date)",
            default => "DATE_FORMAT(payment_date, '%Y-%m')",
        };
    }
}
