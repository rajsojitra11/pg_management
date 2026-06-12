@extends('layouts.app-tw')
@section('title', __('tenant::message.detail'))
@section('nav-module', 'tenant')
@section('breadcrumb', 'Home > Tenant > View')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h5 class="text-lg font-semibold text-zinc-900">{{ __('tenant::message.detail') }}</h5>
    @can('tenant-list')
    <a href="{{ route('tenant.index') }}" class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center">
        <i class="fa-solid fa-arrow-left mr-1.5 text-xs"></i> {{ __('message.common.back') }}
    </a>
    @endcan
</div>

<div class="space-y-5">
    {{-- Section 1: Basic Information --}}
    <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
        <div class="px-5 py-3 border-b border-zinc-200">
            <h6 class="text-sm font-semibold text-zinc-900">{{ __('tenant::message.section_pg_personal') }}</h6>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.name') }}</p>
                <p class="text-sm font-semibold text-zinc-900">{{ $tenant->name ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.email') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->email ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.mobile') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->phone ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.pg') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->pg?->pg_name ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.room') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->room?->room_no ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.bed_no') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->bed_no ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.date_of_birth') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->date_of_birth ? $tenant->date_of_birth->format('d-m-Y') : '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.gender') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->gender ? ucfirst($tenant->gender) : '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.occupation') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->occupation ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('message.common.status') }}</p>
                <p class="text-sm">
                    @php $statusClass = $tenant->status === 'active' ? 'text-green-700 bg-green-50 border-green-200' : 'text-zinc-700 bg-zinc-50 border-zinc-200'; @endphp
                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium border {{ $statusClass }}">
                        {{ $tenant->status ? ucfirst($tenant->status) : '-' }}
                    </span>
                </p>
            </div>
        </div>
    </div>

    {{-- Section 2: Stay & Payment --}}
    <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
        <div class="px-5 py-3 border-b border-zinc-200">
            <h6 class="text-sm font-semibold text-zinc-900">{{ __('tenant::message.section_stay_payment') }}</h6>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.checkin_date') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->checkin_date ? $tenant->checkin_date->format('d-m-Y') : '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.expected_checkout_date') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->expected_checkout_date ? $tenant->expected_checkout_date->format('d-m-Y') : '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.monthly_rent') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->monthly_rent ? '₹' . number_format($tenant->monthly_rent, 2) : '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.security_deposit') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->security_deposit ? '₹' . number_format($tenant->security_deposit, 2) : '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.payment_method') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->payment_method ? ucfirst(str_replace('_', ' ', $tenant->payment_method)) : '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.id_proof_type') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->id_proof_type ? ucfirst(str_replace('_', ' ', $tenant->id_proof_type)) : '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.id_proof_number') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->id_proof_number ?? '-' }}</p>
            </div>
            @if ($tenant->id_proof_file)
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.id_proof_file') }}</p>
                    <a href="{{ Storage::url($tenant->id_proof_file) }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 underline inline-flex items-center">
                    <i class="fa-solid fa-file mr-1 text-xs"></i> View File
                </a>
            </div>
            @endif
        </div>
    </div>

    {{-- Section 3: Emergency Contact & Permanent Address --}}
    <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
        <div class="px-5 py-3 border-b border-zinc-200">
            <h6 class="text-sm font-semibold text-zinc-900">{{ __('tenant::message.section_emergency_address') }}</h6>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.emergency_contact_name') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->emergency_contact_name ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.emergency_relation') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->emergency_relation ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.emergency_contact_number') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->emergency_contact_number ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.permanent_state') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->permanentState?->name ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.permanent_city') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->permanentCity?->name ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.permanent_address') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->permanent_address ?? '-' }}</p>
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.additional_notes') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->additional_notes ?? '-' }}</p>
            </div>
        </div>
    </div>

    {{-- Section 4: Audit Information --}}
    <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
        <div class="px-5 py-3 border-b border-zinc-200">
            <h6 class="text-sm font-semibold text-zinc-900">{{ __('message.common.record_details') }}</h6>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('message.common.created_at') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->created_at ? $tenant->created_at->format('d-m-Y H:i:s') : '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('message.common.updated_at') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->updated_at ? $tenant->updated_at->format('d-m-Y H:i:s') : '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('message.common.created_by') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->createdBy?->email ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('message.common.updated_by') }}</p>
                <p class="text-sm text-zinc-900">{{ $tenant->updatedBy?->email ?? '-' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
