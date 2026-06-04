@extends('layouts.app-tw')
@section('title', __('role::message.list'))
@section('nav-module', 'roles')
@section('breadcrumb', __('role::message.list'))

@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between">
        <h5 class="text-lg font-semibold text-zinc-900">{{ __('role::message.list') }}</h5>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    {{-- Add New Role card --}}
    @can('role-create')
    <a href="{{ route('roles.create') }}"
       class="group rounded-lg border border-dashed border-zinc-300 p-5 flex flex-col items-center justify-center gap-3 transition-colors hover:border-zinc-900 hover:bg-zinc-50"
       style="min-height: 140px;">
        <div class="h-10 w-10 rounded-full flex items-center justify-center" style="background-color: var(--erp-primary); color: var(--erp-primary-fg);">
            <i class="fa-solid fa-plus text-sm"></i>
        </div>
        <div class="text-center">
            <p class="text-sm font-semibold text-zinc-900">{{ __('role::message.add') }}</p>
            <p class="text-xs text-zinc-500 mt-0.5">{{ __('role::message.if_not') }}</p>
        </div>
    </a>
    @endcan

    {{-- Role cards --}}
    @foreach ($roles as $role)
    <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-zinc-500">Total {{ $role->users->count() }} users</span>
            <div class="flex -space-x-2">
                @foreach ($role->users->take(5) as $usr)
                <div class="h-7 w-7 rounded-full border-2 border-white flex items-center justify-center text-xs font-medium shrink-0"
                     style="background-color: var(--erp-bg-muted); color: var(--erp-text-secondary);"
                     title="{{ $usr->name }}">
                    {{ strtoupper(substr($usr->name, 0, 1)) }}
                </div>
                @endforeach
                @if ($role->users->count() > 5)
                <div class="h-7 w-7 rounded-full border-2 border-white flex items-center justify-center text-xs font-medium shrink-0"
                     style="background-color: var(--erp-primary); color: var(--erp-primary-fg);">
                    +{{ $role->users->count() - 5 }}
                </div>
                @endif
            </div>
        </div>
        <div class="flex items-end justify-between">
            <div>
                <h6 class="text-base font-semibold text-zinc-900 mb-0.5">{{ str_replace('_', ' ', $role->name) }}</h6>
                @can('role-edit')
                <a href="{{ route('roles.edit', $role->id) }}" class="text-sm font-medium transition-colors" style="color: var(--erp-primary);">
                    {{ __('role::message.edit') }}
                </a>
                @endcan
            </div>
            @if ($role->id != 1 && $role->id != 2 && $role->id != 3)
                @can('role-delete')
                <a href="javascript:void(0);" data-id="{{ $role->id }}" class="delete p-1.5 rounded-md text-red-500 hover:bg-red-50 transition-colors">
                    <i class="fa-solid fa-trash text-xs"></i>
                </a>
                @endcan
            @endif
        </div>

        {{-- Year Access --}}
        @php $ya = roleYearAccessSummary($role); @endphp
        <div class="mt-4 pt-3 border-t border-zinc-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-zinc-500">{{ __('role::message.year_access') }}</span>
                @if ($ya['restricted'])
                <span class="inline-flex items-center rounded-md border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700">
                    <i class="fa-solid fa-shield-halved mr-1 text-[10px]"></i> {{ __('role::message.restricted') }}
                </span>
                @else
                <span class="inline-flex items-center rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">
                    <i class="fa-solid fa-circle-check mr-1 text-[10px]"></i> {{ __('role::message.full_access') }}
                </span>
                @endif
            </div>
            @if ($ya['restricted'])
            <div class="flex flex-wrap gap-1.5">
                @foreach ($ya['years'] as $yr)
                <span class="inline-flex items-center rounded-md border border-zinc-200 bg-zinc-50 px-2.5 py-0.5 text-xs font-medium text-zinc-700">{{ getFormattedYear($yr) }}</span>
                @endforeach
            </div>
            @else
            <p class="text-xs text-zinc-500">{{ __('role::message.all_years_access') }}</p>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endsection

@section('pagescript')
<script>
    window.URL_ROUTE = "{{ route('roles.index') }}";
</script>
@endsection
