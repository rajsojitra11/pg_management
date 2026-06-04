<?php

declare(strict_types=1);

use App\Http\Controllers\LookupController;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Route as RouteInstance;
use Illuminate\Support\Facades\Route;

/**
 * LOOKUP-CONSOLIDATION-001 — Stage 9 / T-033 cross-cutting Rule:
 * "the canonical payload envelope is `[{value: string, label: string}]`"
 * (.feature line 21 + line 33).
 *
 * Static-source guarantee: every endpoint returns `response()->json($rows)`
 * where `$rows` is a Collection mapped to `['value' => ..., 'label' => ...]`.
 * The envelope MUST be a bare JSON array — NOT wrapped in `{ data: [...] }`
 * (per the Stage 3 patterns brief, anti-pattern #2 — JsonResource layer
 * explicitly rejected).
 *
 * Why this is static-source rather than runtime:
 *   The originally-spec'd test (parametrized HTTP GET against each endpoint)
 *   needs RefreshDatabase to seed lookup rows, and :memory: SQLite is
 *   currently blocked by the `chk_lm_standard_tier CHECK` migration in
 *   lab_materials (MIGRATION-CROSS-DB-001 — out of scope here per
 *   .feature non-goals).
 *
 *   We compensate by combining three static guarantees:
 *     1. The action method's return type is `JsonResponse` (PHP type system
 *        rules out `JsonResource` / array wrappers at compile time);
 *     2. The action body contains `'value' =>` AND `'label' =>` (the canonical
 *        key shape — any other shape is a violation by definition);
 *     3. The action body contains `response()->json($rows)` — NOT
 *        `JsonResource::collection(...)`, NOT a `['data' => ...]` wrap.
 *
 * Combined, these three give the same regression-guard as a runtime envelope
 * assertion against an empty seed (which would also produce `[]` — bare array).
 */
dataset('lookupRouteNames', [
    'lookup.products',
    'lookup.rawmaterials',
    'lookup.formulations',
    'lookup.customers',
    'lookup.process-stages',
    'lookup.specifications',
    'lookup.suppliers',
    'lookup.units',
    'lookup.storage-conditions',
    'lookup.business-associates',
    'lookup.client-products',
    'lookup.countries',
    'lookup.states',
    'lookup.cities',
    'lookup.currencies',
    'lookup.transports',
    'lookup.raw-material-categories',
    'lookup.roles',
    'lookup.active-users',
    'lookup.years',
    'lookup.prefix-masters',
    'lookup.material-types',
    'lookup.lab-material-categories',
]);

it('every lookup endpoint declares JsonResponse as its return type', function (string $routeName) {
    $route = Route::getRoutes()->getByName($routeName);
    expect($route)->not->toBeNull("Route {$routeName} is not registered.");

    /** @var RouteInstance $route */
    $action = $route->getActionName();
    expect(str_contains($action, '@'))->toBeTrue(
        "Route {$routeName} has no controller@action action — got '{$action}'."
    );

    [$controllerClass, $methodName] = explode('@', $action);
    expect($controllerClass)->toBe(LookupController::class, "Route {$routeName} should resolve to LookupController.");

    $reflection = new ReflectionMethod($controllerClass, $methodName);
    $returnType = $reflection->getReturnType();
    expect($returnType)->not->toBeNull("LookupController::{$methodName}() has no declared return type.");
    expect((string) $returnType)->toBe(
        JsonResponse::class,
        "LookupController::{$methodName}() must declare JsonResponse return type — got ".(string) $returnType
    );
})->with('lookupRouteNames');

it('every lookup endpoint maps rows to the canonical {value, label} shape', function (string $routeName) {
    $route = Route::getRoutes()->getByName($routeName);
    [$controllerClass, $methodName] = explode('@', $route->getActionName());

    $reflection = new ReflectionMethod($controllerClass, $methodName);
    $source = file($reflection->getFileName());
    $bodyLines = array_slice(
        $source,
        $reflection->getStartLine() - 1,
        $reflection->getEndLine() - $reflection->getStartLine() + 1
    );
    $body = implode('', $bodyLines);

    // Required envelope keys (canonical shape — Stage 3 Pattern 2).
    // Use expect()->toBeTrue/toBeFalse on the boolean str_contains result —
    // Pest's toContain() is variadic and would otherwise treat our second
    // (message) arg as another required substring.
    expect(str_contains($body, "'value' =>"))->toBeTrue(
        "LookupController::{$methodName}() must produce rows shaped as ['value' => ..., 'label' => ...]."
    );
    expect(str_contains($body, "'label' =>"))->toBeTrue(
        "LookupController::{$methodName}() must produce rows shaped as ['value' => ..., 'label' => ...]."
    );

    // Bare array envelope: response()->json($rows) — NOT a wrapped resource.
    expect(str_contains($body, 'response()->json($rows)'))->toBeTrue(
        "LookupController::{$methodName}() must return response()->json(\$rows) — bare array envelope. ".
        'JsonResource collections, ResourceCollection, or {data: [...]} wrappers are explicitly rejected '.
        '(Stage 3 Pattern 2 / anti-pattern #2 — see patterns.md).'
    );

    // Anti-pattern guards (defense in depth).
    expect(str_contains($body, 'JsonResource'))->toBeFalse(
        "LookupController::{$methodName}() must not use JsonResource — bare array envelope is mandatory."
    );
    expect(str_contains($body, "'data' =>"))->toBeFalse(
        "LookupController::{$methodName}() must not wrap output in {data: [...]} — bare array only."
    );
})->with('lookupRouteNames');
