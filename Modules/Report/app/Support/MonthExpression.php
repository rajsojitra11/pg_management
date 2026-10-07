<?php

namespace Modules\Report\Support;

use InvalidArgumentException;

class MonthExpression
{
    /**
     * Driver safe SQL expression that renders a date column as a `Y-m` month key.
     *
     * @throws InvalidArgumentException when the column name is not a plain identifier
     */
    public static function for(string $driver, string $column = 'payment_date'): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $column) !== 1) {
            throw new InvalidArgumentException("Invalid month expression column [{$column}].");
        }

        return match ($driver) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            'pgsql' => "TO_CHAR({$column}, 'YYYY-MM')",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }
}
