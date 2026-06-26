# Events And Observers

## Observers

No dedicated `*Observer.php` files were found in the current project tree.

## Trait-Based Model Events

`App\Traits\HasActivityLogging` is the main event-like mechanism. Models using
it log create/update/delete activity to module-specific log models/tables.

## Trigger Conditions

Typical triggers:

- model created
- model updated
- model deleted
- custom log calls through trait helper methods

## Side Effects

- Activity log row creation.
- Old/new value capture.
- User id and audit metadata capture.
- Request IP/user-agent/device/browser/platform capture.

## Auto Recalculation Logic

No global observer-driven recalculation system was found. Dashboard calculations
are service/query driven.
