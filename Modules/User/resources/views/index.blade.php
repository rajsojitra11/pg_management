@extends('layouts.app-tw')
@section('title', __('user::message.list'))
@section('nav-module', 'users')
@section('breadcrumb', __('user::message.list'))

@section('content')
{{-- Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ __('user::message.list') }}</h1>
        <p class="text-sm text-zinc-500 mt-0.5">Manage all system users and their roles</p>
    </div>
    @can('users-create')
    <a href="{{ route('users.create') }}" class="h-9 px-4 rounded-md text-sm font-medium whitespace-nowrap inline-flex items-center gap-2 transition-colors" style="background-color: var(--erp-primary); color: var(--erp-primary-fg);">
        <i class="fa-solid fa-plus text-xs"></i> {{ __('user::message.add') }}
    </a>
    @endcan
</div>

{{-- Filter Bar --}}
<form id="filter_form" class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm mb-4" onsubmit="return false;">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 lg:items-end">
        <div class="lg:col-span-5">
            <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('user::message.search') }}</label>
            <div class="flex h-9 rounded-md border border-zinc-200 bg-white focus-within:ring-2 focus-within:ring-zinc-900 focus-within:ring-offset-2 overflow-hidden">
                <span class="inline-flex items-center px-3 bg-zinc-50 border-r border-zinc-200 text-zinc-400 text-xs"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" id="filterSearch" name="filter_search" placeholder="{{ __('user::message.search_placeholder') }}" class="flex-1 min-w-0 bg-transparent px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:outline-none">
            </div>
        </div>
        <div class="lg:col-span-2">
            <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('user::message.role') }}</label>
            <select id="filterRole" name="filter_role" class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500">
                <option value="">{{ __('message.common.select') }}</option>
                @foreach ($roles as $role)
                <option value="{{ $role }}">{{ $role }}</option>
                @endforeach
            </select>
        </div>
        <div class="lg:col-span-2">
            <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('user::message.status') }}</label>
            <select id="filterStatus" name="filter_status" class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500">
                <option value="">{{ __('message.common.select') }}</option>
                <option value="Active">{{ __('user::message.status_active') }}</option>
                <option value="Inactive">{{ __('user::message.status_inactive') }}</option>
            </select>
        </div>
        <div class="lg:col-span-3 flex items-center gap-2 justify-end">
            <button type="button" class="search h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800">{{ __('user::message.apply') }}</button>
            <button type="button" class="reset h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm text-zinc-500 hover:bg-zinc-50">{{ __('user::message.reset') }}</button>
        </div>
    </div>
</form>

{{-- DataTable Card --}}
<div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
    <div class="p-4">
        <table id="users-table" class="w-full text-sm">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('user::message.name') }}</th>
                    <th>{{ __('user::message.mobile_number') }}</th>
                    <th>{{ __('user::message.email') }}</th>
                    <th>{{ __('user::message.user_name') }}</th>
                    <th>{{ __('user::message.designation') }}</th>
                    <th>{{ __('user::message.role') }}</th>
                    <th>Status</th>
                    <th>{{ __('message.common.action') }}</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>


@endsection

@section('pagescript')
<script>
$(document).ready(function() {
    window.URL_ROUTE = "{{ route('users.index') }}";

    // DataTable
    table = initErpTable('#users-table', {
        serverSide: true,
        ajax: {
            url: window.URL_ROUTE,
            data: function (d) {
                d.filter_search = $('#filterSearch').val();
                d.filter_role   = $('#filterRole').val();
                d.filter_status = $('#filterStatus').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '40px' },
            { data: 'name', name: 'name' },
            { data: 'mobile', name: 'mobile' },
            { data: 'email', name: 'email' },
            { data: 'username', name: 'username' },
            { data: 'designation', name: 'designation' },
            { data: 'role', name: 'role', orderable: false, searchable: false },
            { data: 'status_display', name: 'status_display', orderable: false, searchable: false, width: '80px' },
            { data: 'action', name: 'action', orderable: false, searchable: false, width: '120px' },
        ]
    });


});
</script>
@endsection
