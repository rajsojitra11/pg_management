@extends('layouts.app-tw')
@section('title', __('report::message.tenant_report'))
@section('nav-module', 'report')
@section('breadcrumb', 'Home > Reports > Tenant Report')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ __('report::message.tenant_report') }}</h1>
        <p class="text-sm text-zinc-500 mt-1">{{ __('report::message.view_report') }}</p>
    </div>
    <a href="{{ route('report.index') }}" class="h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm text-zinc-500 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center">
        <i class="fa-solid fa-arrow-left mr-1.5 text-xs"></i> {{ __('report::message.report_center') }}
    </a>
</div>

{{-- Filter Bar --}}
<form id="filter_form" class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm mb-4" onsubmit="return false;">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 lg:items-end">
        <div class="lg:col-span-3">
            <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('message.common.search') }}</label>
            <div class="flex h-9 rounded-md border border-zinc-200 bg-white focus-within:ring-2 focus-within:ring-zinc-900 focus-within:ring-offset-2 overflow-hidden">
                <span class="inline-flex items-center px-3 bg-zinc-50 border-r border-zinc-200 text-zinc-400 text-xs"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" id="filterSearch" name="filter_search" placeholder="{{ __('report::message.tenant_search') }}" class="flex-1 min-w-0 bg-transparent px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:outline-none">
            </div>
        </div>
        <div class="lg:col-span-2">
            <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('report::message.from_date') }}</label>
            <input type="text" id="filterFrom" name="filter_from" class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700" autocomplete="off">
        </div>
        <div class="lg:col-span-2">
            <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('report::message.to_date') }}</label>
            <input type="text" id="filterTo" name="filter_to" class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700" autocomplete="off">
        </div>
        <div class="lg:col-span-2">
            <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('report::message.status') }}</label>
            <select id="filterStatus" name="filter_status" class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500">
                <option value="">{{ __('report::message.select_status') }}</option>
                <option value="active">{{ __('report::message.status_active') }}</option>
                <option value="inactive">{{ __('report::message.status_inactive') }}</option>
            </select>
        </div>
        <div class="lg:col-span-2">
            <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('report::message.tenant') }}</label>
            <select id="filterTenant" name="filter_tenant" class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700">
                <option value="">{{ __('message.common.all') }}</option>
                @foreach($tenantList as $t)
                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="lg:col-span-1">
            <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('report::message.room_no') }}</label>
            <select id="filterRoom" name="filter_room" class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700">
                <option value="">{{ __('message.common.all') }}</option>
                @foreach($roomList as $r)
                    <option value="{{ $r->id }}">{{ $r->room_no }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="flex items-center gap-2 justify-end mt-3">
        <button type="button" class="search h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800">{{ __('report::message.apply') }}</button>
        <button type="button" class="reset h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm text-zinc-500 hover:bg-zinc-50">{{ __('report::message.reset') }}</button>
        <button type="button" class="excel h-9 px-3 rounded-md bg-green-700 text-white text-sm font-medium hover:bg-green-800" data-export-url="{{ route('report.tenants.export') }}"><i class="fa-solid fa-file-excel mr-1"></i> {{ __('report::message.excel') }}</button>
    </div>
</form>

{{-- DataTable Card --}}
<div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
    <div class="p-4 overflow-x-auto">
        <table id="table" class="display responsive nowrap w-full">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('report::message.tenant') }}</th>
                    <th>{{ __('report::message.email') }}</th>
                    <th>{{ __('report::message.phone') }}</th>
                    <th>{{ __('report::message.room_no') }}</th>
                    <th>{{ __('report::message.checkin_date') }}</th>
                    <th>{{ __('report::message.expected_checkout_date') }}</th>
                    <th>{{ __('report::message.monthly_rent') }}</th>
                    <th>{{ __('report::message.status') }}</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection

@section('pagescript')
<script type="application/javascript">
    'use strict';
    var table = '';
    var fromPicker = null;
    var toPicker = null;

    function initFilterSelects() {
        if (typeof initErpSelect === 'function') {
            initErpSelect('#filterTenant', { placeholder: "{{ __('message.common.all') }}", allowClear: true });
            initErpSelect('#filterRoom', { placeholder: "{{ __('message.common.all') }}", allowClear: true });
        }
    }

    $(function() {
        if (typeof flatpickr === 'function') {
            fromPicker = flatpickr('#filterFrom', {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd-m-Y',
                allowInput: true
            });
            toPicker = flatpickr('#filterTo', {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd-m-Y',
                allowInput: true
            });
        }

        initFilterSelects();

        table = initErpTable('#table', {
            ajax: {
                url: "{{ route('report.tenants') }}",
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                data: function (d) {
                    d.filter_search = $('#filterSearch').val();
                    d.filter_from = $('#filterFrom').val();
                    d.filter_to = $('#filterTo').val();
                    d.filter_status = $('#filterStatus').val();
                    d.filter_tenant = $('#filterTenant').val();
                    d.filter_room = $('#filterRoom').val();
                }
            },
            processing: true,
            serverSide: true,
            scrollX: true,
            aLengthMenu: [
                [15, 30, 50, 100, -1],
                [15, 30, 50, 100, "All"]
            ],
            order: [[5, 'desc']],
            columns: [
                { data: 'id', render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; }, orderable: false, width: '50px' },
                { data: 'name', name: 'name' },
                { data: 'email', name: 'email' },
                { data: 'phone', name: 'phone' },
                { data: 'room_no', name: 'room_no', orderable: false, searchable: false },
                { data: 'checkin_date', name: 'checkin_date', render: function(data) { return window.erpDate ? window.erpDate(data) : (data || '-'); } },
                { data: 'expected_checkout_date', name: 'expected_checkout_date', render: function(data) { return window.erpDate ? window.erpDate(data) : (data || '-'); } },
                { data: 'monthly_rent', name: 'monthly_rent', render: function(data) { return data ? '₹' + parseFloat(data).toFixed(2) : '-'; } },
                { data: 'status', name: 'status', render: function(data) { return window.erpBadge ? window.erpBadge(data) : (data || '-'); } }
            ]
        });

        $(document).on('click', '#filter_form .search', function() {
            table.ajax.reload();
        });

        $(document).on('click', '#filter_form .reset', function() {
            $('#filter_form')[0].reset();
            if (fromPicker) fromPicker.clear();
            if (toPicker) toPicker.clear();
            if (typeof cleanupErpSelect === 'function') {
                cleanupErpSelect('#filterTenant');
                cleanupErpSelect('#filterRoom');
            }
            initFilterSelects();
            table.ajax.reload();
        });

        $(document).on('click', '#filter_form .excel', function() {
            var params = {
                filter_search: $('#filterSearch').val() || '',
                filter_from: $('#filterFrom').val() || '',
                filter_to: $('#filterTo').val() || '',
                filter_status: $('#filterStatus').val() || '',
                filter_tenant: $('#filterTenant').val() || '',
                filter_room: $('#filterRoom').val() || ''
            };
            window.location.href = $(this).data('export-url') + '?' + $.param(params);
        });
    });
</script>
@endsection