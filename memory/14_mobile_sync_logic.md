# Mobile Sync Logic

## Current State

No dedicated offline mobile sync implementation was found.

## Offline Strategy

Not implemented. If added, use an explicit sync table with:

- client id
- entity type
- entity public id
- operation
- payload JSON
- client timestamp
- server received timestamp
- sync status
- conflict reason

## Conflict Resolution

Not implemented. Recommended rule:

- Server is authoritative for persisted data.
- `updated_at` is the server timestamp authority.
- Conflicts must compare client base version against server current version.
- Verified/locked records should reject offline updates.

## Sync Queue Structure

Not implemented. Do not reuse Laravel queue tables as mobile sync state.

## Timestamp Authority Rule

Server timestamps must be authoritative.

## Lock Handling

- Verified payments should reject mobile edits unless an override permission is
  explicitly granted.
- Deleted/soft-deleted records should return conflict responses, not recreate
  silently.
