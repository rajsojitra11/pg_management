<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a value resolves to an existing record by EITHER the numeric
 * primary key OR the ULID `public_id`, using the model's HasPublicId helpers.
 *
 * Drop-in replacement for `exists:<table>,id` on request fields that now carry
 * a `public_id` instead of the numeric id. Soft-deleted rows are excluded via
 * the model's default global scope (matching the old `...,deleted_at,NULL`).
 *
 * Usage: 'id' => ['required', new ExistsByAnyKey(Vendor::class)]
 *
 * @param  class-string  $modelClass  a model using App\Traits\HasPublicId
 */
class ExistsByAnyKey implements ValidationRule
{
    public function __construct(private string $modelClass, private ?string $messageKey = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '' || ! method_exists($this->modelClass, 'findByAnyKey')) {
            $fail($this->messageKey ?? 'validation.exists')->translate(['attribute' => $attribute]);

            return;
        }

        if ($this->modelClass::findByAnyKey($value) === null) {
            $fail($this->messageKey ?? 'validation.exists')->translate(['attribute' => $attribute]);
        }
    }
}
