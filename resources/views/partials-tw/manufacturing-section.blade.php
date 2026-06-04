{{--
    Manufacturing Section Partial — Tailwind Theme

    PR 8: Renders the Manufacturing (route binding) fields on a material form
    (Product / Rawmaterial / Formulation).

    Usage:
    @include('partials-tw.manufacturing-section', ['routeType' => 'final_product'])
    @include('partials-tw.manufacturing-section', ['routeType' => 'raw_material', 'model' => $rawmaterial])

    Parameters:
    - routeType: 'final_product' | 'raw_material' | 'byproduct' | 'intermediate'
    - model: optional existing model for edit forms (auto-populates fields)

    Conditional display:
    - For Rawmaterial: only shown when source_kind != 'purchased' (a purchased
      raw material isn't manufactured, so route binding is N/A).
    - For Product / Formulation: always shown.
--}}

@php
    $routeType = $routeType ?? 'final_product';
    $existing = $model ?? null;

    $routeRequired = old('route_required', $existing?->route_required ?? 0);
    $processRouteId = old('process_route_id', $existing?->process_route_id);

    // Hide for purchased rawmaterials (no manufacturing applies)
    $isPurchasedRm = ($existing?->source_kind ?? null) === 'purchased' && $routeType === 'raw_material';

    $approvedRoutes = \Modules\ProcessRoute\Models\ProcessRoute::query()
        ->where('type', $routeType)
        ->where('status', \Modules\ProcessRoute\Models\ProcessRoute::STATUS_APPROVED)
        ->whereNotNull('current_version_id')
        ->orderBy('code')
        ->get(['id', 'code', 'name', 'revision_no']);
@endphp

@unless ($isPurchasedRm)
    <div class="col-span-1 sm:col-span-2 lg:col-span-3 mt-4 mb-2">
        <h3 class="text-base font-semibold border-b pb-2" style="color: var(--erp-text); border-color: var(--erp-border);">
            Manufacturing
        </h3>
    </div>

    <div>
        <label class="block text-sm font-medium mb-1" style="color: var(--erp-text);">
            Route Required
        </label>
        <div class="flex items-center gap-3 mt-1">
            <label class="inline-flex items-center cursor-pointer">
                <input type="hidden" name="route_required" value="0">
                <input type="checkbox"
                    name="route_required"
                    value="1"
                    id="route_required_checkbox"
                    @checked((int) $routeRequired === 1)
                    class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm" style="color: var(--erp-text);">Yes — production must bind to a route</span>
            </label>
        </div>
        @error('route_required')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="process_route_id" class="block text-sm font-medium mb-1" style="color: var(--erp-text);">
            Default Process Route
            <span class="text-xs text-zinc-500">(only Approved shown)</span>
        </label>
        <select name="process_route_id" id="process_route_id"
            class="w-full rounded-md border-zinc-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
            <option value="">— Resolve at production-start —</option>
            @foreach ($approvedRoutes as $route)
                <option value="{{ $route->id }}" @selected((int) $processRouteId === (int) $route->id)>
                    {{ $route->code }} — {{ $route->name }} (rev {{ $route->revision_no }})
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-zinc-500">
            If left blank, ProcessRouteRegistry resolves at production-start (customer-specific → in-house default).
        </p>
        @error('process_route_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
@endunless
