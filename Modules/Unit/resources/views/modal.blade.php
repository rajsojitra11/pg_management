<div class="p-4 border-b border-zinc-200">
    <h5 class="text-lg font-semibold text-zinc-700">{{ __('unit::message.unit') }} : {{ $unit->name ?? '' }}</h5>
</div>
<div class="p-4">
    <div class="rounded-md border border-zinc-200">
        <dl class="divide-y divide-zinc-200">
            <div class="grid grid-cols-3 gap-4 px-4 py-3">
                <dt class="text-sm font-medium text-zinc-500">{{ __('unit::message.name') }}</dt>
                <dd class="col-span-2 text-sm text-zinc-700">{{ $unit->name }}</dd>
            </div>
            <div class="grid grid-cols-3 gap-4 px-4 py-3">
                <dt class="text-sm font-medium text-zinc-500">{{ __('unit::message.unit_value') }}</dt>
                <dd class="col-span-2 text-sm text-zinc-700">{{ $unit->unit_value }}</dd>
            </div>
        </dl>
    </div>
</div>
