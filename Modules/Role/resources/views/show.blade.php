@extends('layouts.app-tw')
@section('title', __('role::message.show_role'))
@section('nav-module', 'roles')
@section('breadcrumb', __('role::message.show_role'))

@section('content')
<div class="flex items-center justify-between mb-6">
    <h5 class="text-lg font-semibold text-zinc-900">{{ $role->name }}</h5>
    <a href="{{ route('roles.index') }}" class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center">
        <i class="fa-solid fa-arrow-left mr-1.5 text-xs"></i> {{ __('message.common.back') }}
    </a>
</div>

<div class="rounded-lg border border-zinc-200 bg-white shadow-sm p-5">
    <div class="mb-4">
        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('role::message.name') }}</span>
        <p class="text-base font-semibold text-zinc-900 mt-0.5">{{ $role->name }}</p>
    </div>

    <div>
        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400 mb-2 block">{{ __('role::message.permissions') }}</span>
        @if(!empty($rolePermissions))
            <div class="flex flex-wrap gap-1.5">
                @foreach($rolePermissions as $v)
                    <span class="inline-flex items-center whitespace-nowrap rounded-md border px-2.5 py-0.5 text-xs font-medium"
                          style="background-color: var(--erp-bg-muted); color: var(--erp-text-secondary); border-color: var(--erp-border);">
                        {{ $v->name }}
                    </span>
                @endforeach
            </div>
        @else
            <p class="text-sm text-zinc-400">No permissions assigned.</p>
        @endif
    </div>
</div>
@endsection
