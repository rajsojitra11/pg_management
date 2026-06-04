<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Restricts a query to the financial years the current user's role(s) may view.
 *
 * Relies on the `allowedYearIds()` helper:
 *   - null  => unrestricted (no constraint added).
 *   - array => constrain to `whereIn('year_id', $ids)` (table-qualified so it is
 *              unambiguous in queries that join). An empty array yields zero rows
 *              (fail-closed for a misconfigured restricted role).
 *
 * Every model using this trait must expose a local `year_id` column.
 */
trait HasYearAccessScope
{
    public function scopeVisibleForYearAccess(Builder $query): Builder
    {
        $ids = allowedYearIds();

        if (is_null($ids)) {
            return $query; // unrestricted
        }

        return $query->whereIn($this->getTable().'.year_id', $ids);
    }
}
