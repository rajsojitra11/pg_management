<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\User\Models\User;

/**
 * Centralised access resolver. Combines the spatie-permission check (role
 * permission like `items-edit`) with the per-record ownership /
 * segregation-of-duties rules (creator, prepared_by, manager_id, head_id,
 * reviewed_by, etc.) defined on the entity.
 *
 * Usage:
 *   AccessGate::check('edit', $item)               // current auth user
 *   AccessGate::check('approve', $item, $someUser)
 *   AccessGate::check('create', Item::class)
 *
 * Models that want to participate in record-level rules implement the
 * {@see AccessAuthorizable} contract by exposing `authorizeAccess()`.
 * Models without it pass through with permission check only.
 *
 * Action vocabulary: create, edit, delete, review, approve, reject.
 */
class AccessGate
{
    /**
     * Resolve whether $user (or auth user) may perform $action on $entity.
     */
    public static function check(string $action, Model|string|null $entity = null, ?User $user = null): bool
    {
        $user ??= auth()->user();
        if (! $user) {
            return false;
        }

        $permission = static::permissionName($entity, $action);
        if ($permission && ! $user->can($permission)) {
            return false;
        }

        if (is_object($entity) && method_exists($entity, 'authorizeAccess')) {
            return (bool) $entity->authorizeAccess($action, $user);
        }

        return true;
    }

    /**
     * Build the spatie permission slug. Convention: <kebab-class>-<action>
     * e.g. `item-edit`, `menumaster-approve`. The action `reject` reuses
     * the `edit` permission since rejection is a write operation under
     * the project's audit discipline.
     */
    public static function permissionName(Model|string|null $entity, string $action): ?string
    {
        if ($entity === null) {
            return null;
        }
        $class = is_object($entity) ? $entity::class : $entity;
        $slug = Str::kebab(class_basename($class));
        $slug = str_replace('-', '', $slug);  // collapse to single word: "menumaster"

        $perm = match ($action) {
            'reject' => 'edit',
            default => $action,
        };

        return $slug.'-'.$perm;
    }
}
