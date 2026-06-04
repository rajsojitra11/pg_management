@extends('layouts.app-tw')
@section('title', __('country::message.country'))
@section('nav-module', 'country')
@section('breadcrumb', 'Home > Masters > Country > View')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h5 class="text-lg font-semibold" style="color: var(--erp-text);">{{ __('country::message.country') }}</h5>
    @can('country-list')
    <a href="{{ route('country.index') }}" class="erp-modal-btn-secondary">
        <i class="fa-solid fa-arrow-left mr-1.5 text-xs"></i> {{ __('message.common.back') }}
    </a>
    @endcan
</div>

<div class="rounded-lg border bg-white shadow-sm p-5" style="border-color: var(--erp-border); background-color: var(--erp-bg);">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <p class="text-xs font-medium mb-1" style="color: var(--erp-text-secondary);">{{ __('country::message.name') }}</p>
            <p class="text-sm font-semibold" style="color: var(--erp-text);">{{ $country->name ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs font-medium mb-1" style="color: var(--erp-text-secondary);">{{ __('country::message.code') }}</p>
            <p class="text-sm font-semibold" style="color: var(--erp-text);">{{ $country->code ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs font-medium mb-1" style="color: var(--erp-text-secondary);">{{ __('message.common.created_at') }}</p>
            <p class="text-sm" style="color: var(--erp-text);">{{ $country->created_at ? $country->created_at->format('d-m-Y H:i:s') : '-' }}</p>
        </div>
        <div>
            <p class="text-xs font-medium mb-1" style="color: var(--erp-text-secondary);">{{ __('message.common.updated_at') }}</p>
            <p class="text-sm" style="color: var(--erp-text);">{{ $country->updated_at ? $country->updated_at->format('d-m-Y H:i:s') : '-' }}</p>
        </div>
    </div>
</div>
@endsection
