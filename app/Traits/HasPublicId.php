<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

/**
 * Adds a ULID `public_id` column for URL/API addressing while keeping
 * BIGINT auto-increment as the primary key (and as foreign keys everywhere).
 *
 * Dual-resolution route binding:
 * - If the URL parameter is numeric, look up by `id` (BIGINT primary key).
 * - If the URL parameter is a 26-char Crockford-base32 ULID, look up by `public_id`.
 * - Both URL forms resolve to the same model — no 301 redirect needed.
 *
 * `getRouteKeyName()` returns `public_id` so that `route('foo.show', $model)`
 * generates the canonical ULID URL. Code that passes `$model->id` keeps working
 * because dual resolution accepts numeric IDs.
 *
 * For `$id`-style controllers that bypass implicit binding, use the
 * `findByAnyKey()` / `findByAnyKeyOrFail()` helpers below.
 */
trait HasPublicId
{
    protected static function bootHasPublicId(): void
    {
        static::creating(function ($model) {
            if (empty($model->public_id)) {
                $model->public_id = (string) Str::ulid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * Resolve the model from the URL parameter, accepting either the BIGINT id
     * (legacy URLs, current frontend `route(.., $row->id)` patterns) OR the
     * ULID public_id (canonical form).
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field !== null) {
            return $this->where($field, $value)->first();
        }

        $column = self::isUlid($value) ? 'public_id' : 'id';

        return $this->where($column, $value)->first();
    }

    public function scopeByPublicId(Builder $query, string $publicId): Builder
    {
        return $query->where('public_id', $publicId);
    }

    /**
     * Query scope for the dual-key lookup. Use when you need to chain other
     * builder methods (eager loads, filters) with the public-id resolution:
     *   Model::with('items')->byAnyKey($id)->firstOrFail();
     */
    public function scopeByAnyKey(Builder $query, $value): Builder
    {
        $column = self::isUlid($value) ? 'public_id' : 'id';

        return $query->where($column, $value);
    }

    /**
     * Find a record by either its numeric id or its public_id ULID.
     * Drop-in replacement for `Model::find($id)` in controllers that take a
     * plain `$id` parameter and don't use implicit route binding.
     */
    public static function findByAnyKey($value): ?Model
    {
        if ($value === null || $value === '') {
            return null;
        }
        $column = self::isUlid($value) ? 'public_id' : 'id';

        return static::query()->where($column, $value)->first();
    }

    /**
     * Same as findByAnyKey, but throws ModelNotFoundException on miss.
     * Drop-in replacement for `Model::findOrFail($id)`.
     */
    public static function findByAnyKeyOrFail($value): Model
    {
        if ($value === null || $value === '') {
            throw (new ModelNotFoundException)
                ->setModel(static::class, [$value]);
        }
        $column = self::isUlid($value) ? 'public_id' : 'id';

        return static::query()->where($column, $value)->firstOrFail();
    }

    /**
     * Detect whether a value looks like a Crockford-base32 ULID.
     * 26 chars, alphabet 0-9 A-H J K M N P-T V-Z (case-insensitive).
     */
    protected static function isUlid($value): bool
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }
        $value = (string) $value;

        return strlen($value) === 26
            && preg_match('/^[0-9A-HJKMNP-TV-Za-hjkmnp-tv-z]{26}$/', $value) === 1;
    }
}
