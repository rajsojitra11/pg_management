{{--
    Quality Section Partial — Tailwind Theme

    PR 4: Renders the Quality control fields on a material form
    (Product / Rawmaterial / Formulation / UnitOperation).

    Usage:
    @include('partials-tw.quality-section', ['specificationType' => 'final_product'])
    @include('partials-tw.quality-section', ['specificationType' => 'raw_material', 'model' => $product])

    Parameters:
    - specificationType: 'final_product' | 'raw_material' | 'process' | 'byproduct'
    - model: optional existing model for edit forms (auto-populates fields)
--}}

@php
    $specType = $specificationType ?? 'final_product';
    $existing = $model ?? null;

    $testingRequired = old('testing_required', $existing?->testing_required ?? 1);
    $testingMode = old('testing_mode', $existing?->testing_mode ?? 'pass_fail');
    $specificationId = old('specification_id', $existing?->specification_id);

    $customerDimensionEnabled = (bool) env('SPECIFICATION_CUSTOMER_DIMENSION_ENABLED', 0);
    $customerId = old('customer_id', $existing?->customer_id ?? null);

    $approvedSpecs = \Modules\Specification\Models\Specification::query()
        ->where('type', $specType)
        ->where('status', \Modules\Specification\Models\Specification::STATUS_APPROVED)
        ->whereNotNull('current_version_id')
        ->orderBy('code')
        ->get(['id', 'code', 'name', 'revision_no']);
@endphp

<div class="col-span-1 sm:col-span-2 lg:col-span-3 mt-4 mb-2">
    <h3 class="text-base font-semibold border-b pb-2" style="color: var(--erp-text); border-color: var(--erp-border);">
        Quality
    </h3>
</div>

<div>
    <label class="block text-sm font-medium mb-1" style="color: var(--erp-text);">
        Testing Required
    </label>
    <div class="flex items-center gap-3 mt-1">
        <label class="inline-flex items-center cursor-pointer">
            <input type="hidden" name="testing_required" value="0">
            <input type="checkbox"
                name="testing_required"
                value="1"
                id="testing_required_checkbox"
                @checked((int) $testingRequired === 1)
                class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
            <span class="ml-2 text-sm" style="color: var(--erp-text);">Yes — material must be tested before release</span>
        </label>
    </div>
    @error('testing_required')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label for="testing_mode" class="block text-sm font-medium mb-1" style="color: var(--erp-text);">
        Testing Mode
    </label>
    <select name="testing_mode" id="testing_mode"
        class="w-full rounded-md border-zinc-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
        <option value="none" @selected($testingMode === 'none')>None</option>
        <option value="pass_fail" @selected($testingMode === 'pass_fail')>Pass / Fail</option>
        <option value="specification_based" @selected($testingMode === 'specification_based')>Specification Based</option>
    </select>
    @error('testing_mode')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label for="specification_id" class="block text-sm font-medium mb-1" style="color: var(--erp-text);">
        Specification
        <span class="text-xs text-zinc-500">(only Approved shown)</span>
    </label>
    <select name="specification_id" id="specification_id"
        class="w-full rounded-md border-zinc-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
        <option value="">— No specification —</option>
        @foreach ($approvedSpecs as $spec)
            <option value="{{ $spec->id }}" @selected((int) $specificationId === (int) $spec->id)>
                {{ $spec->code }} — {{ $spec->name }} (rev {{ $spec->revision_no }})
            </option>
        @endforeach
    </select>
    @error('specification_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>

@if ($customerDimensionEnabled)
    <div>
        <label for="customer_id" class="block text-sm font-medium mb-1" style="color: var(--erp-text);">
            Customer (CMO scope)
        </label>
        <select name="customer_id" id="customer_id"
            class="w-full rounded-md border-zinc-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
            <option value="">— In-house default —</option>
            @foreach (\Modules\Customer\Models\Customer::query()->orderBy('name')->get(['id', 'name']) as $customer)
                <option value="{{ $customer->id }}" @selected((int) $customerId === (int) $customer->id)>
                    {{ $customer->name }}
                </option>
            @endforeach
        </select>
        @error('customer_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
@endif
