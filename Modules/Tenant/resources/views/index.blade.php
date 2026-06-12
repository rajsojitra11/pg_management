@extends('layouts.app-tw')
@section('title', __('tenant::message.tenant_master'))
@section('nav-module', 'tenant')
@section('breadcrumb', 'Home > Tenant')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ __('tenant::message.list') }}</h1>
    </div>
    <div class="flex items-center gap-2">
        @can('tenant-create')
        <a href="{{ route('tenant.create') }}" class="h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 whitespace-nowrap inline-flex items-center">
            <i class="fa-solid fa-plus mr-1.5 text-xs"></i> {{ __('message.common.addNew') }}
        </a>
        @endcan
    </div>
</div>

{{-- Filter Bar --}}
<form id="filter_form" class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm mb-4" onsubmit="return false;">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 lg:items-end">
        <div class="lg:col-span-6">
            <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('message.common.search') }}</label>
            <div class="flex h-9 rounded-md border border-zinc-200 bg-white focus-within:ring-2 focus-within:ring-zinc-900 focus-within:ring-offset-2 overflow-hidden">
                <span class="inline-flex items-center px-3 bg-zinc-50 border-r border-zinc-200 text-zinc-400 text-xs"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" id="filterSearch" name="filter_search" placeholder="{{ __('tenant::message.search_placeholder') }}" class="flex-1 min-w-0 bg-transparent px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:outline-none">
            </div>
        </div>
        <div class="lg:col-span-4 flex items-center gap-2 justify-end lg:col-start-9">
            <button type="button" class="search h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800">{{ __('tenant::message.apply') }}</button>
            <button type="button" class="reset h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm text-zinc-500 hover:bg-zinc-50">{{ __('tenant::message.reset') }}</button>
        </div>
    </div>
</form>

{{-- DataTable Card --}}
<div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
    <div class="p-4 overflow-x-auto">
        <table id="table" class="display responsive nowrap w-full">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('tenant::message.pg') }}</th>
                    <th>{{ __('tenant::message.room') }}</th>
                    <th>{{ __('tenant::message.checkin_date') }}</th>
                    <th>{{ __('tenant::message.monthly_rent') }}</th>
                    <th>{{ __('tenant::message.phone') }}</th>
                    <th>{{ __('message.common.status') }}</th>
                    <th>{{ __('message.common.action') }}</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- View Modal --}}
<div id="viewModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 erp-inline-modal-close"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="relative w-full max-w-2xl rounded-lg border border-zinc-200 bg-white shadow-xl">
            <div class="flex items-center justify-between p-4 border-b border-zinc-200">
                <h3 class="text-lg font-semibold text-zinc-900" id="viewModalTitle">{{ __('tenant::message.tenant') }}</h3>
                <button type="button" class="text-zinc-400 hover:text-zinc-600 erp-inline-modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-5 space-y-4 max-h-[70vh] overflow-y-auto">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.name') }}</p>
                        <p class="text-sm font-semibold text-zinc-900" id="view_name">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.email') }}</p>
                        <p class="text-sm text-zinc-900" id="view_email">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.mobile') }}</p>
                        <p class="text-sm text-zinc-900" id="view_phone">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.pg') }}</p>
                        <p class="text-sm text-zinc-900" id="view_pg">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.room') }}</p>
                        <p class="text-sm text-zinc-900" id="view_room">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.bed_no') }}</p>
                        <p class="text-sm text-zinc-900" id="view_bed_no">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.checkin_date') }}</p>
                        <p class="text-sm text-zinc-900" id="view_checkin">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.expected_checkout_date') }}</p>
                        <p class="text-sm text-zinc-900" id="view_checkout">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.monthly_rent') }}</p>
                        <p class="text-sm text-zinc-900" id="view_rent">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.security_deposit') }}</p>
                        <p class="text-sm text-zinc-900" id="view_deposit">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.payment_method') }}</p>
                        <p class="text-sm text-zinc-900" id="view_payment_method">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.id_proof_type') }}</p>
                        <p class="text-sm text-zinc-900" id="view_id_proof_type">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.id_proof_number') }}</p>
                        <p class="text-sm text-zinc-900" id="view_id_proof_number">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.gender') }}</p>
                        <p class="text-sm text-zinc-900" id="view_gender">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.date_of_birth') }}</p>
                        <p class="text-sm text-zinc-900" id="view_dob">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.occupation') }}</p>
                        <p class="text-sm text-zinc-900" id="view_occupation">-</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.permanent_address') }}</p>
                        <p class="text-sm text-zinc-900" id="view_address">-</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('tenant::message.additional_notes') }}</p>
                        <p class="text-sm text-zinc-900" id="view_notes">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('message.common.status') }}</p>
                        <p class="text-sm text-zinc-900" id="view_status">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('message.common.created_at') }}</p>
                        <p class="text-sm text-zinc-900" id="view_created_at">-</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 p-4 border-t border-zinc-200">
                <button type="button" class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center erp-inline-modal-close">
                    {{ __('message.common.close') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('pagescript')
<script>
    'use strict';
    window.URL_ROUTE = "{{ route('tenant.index') }}";

    var table = '';

    $(function() {
        table = initErpTable('#table', {
            ajax: {
                url: window.URL_ROUTE,
                data: function (d) {
                    d.filter_search = $('#filterSearch').val();
                }
            },
            processing: true,
            serverSide: true,
            scrollX: true,
            aLengthMenu: [
                [15, 30, 50, 100, -1],
                [15, 30, 50, 100, "All"]
            ],
            order: [[0, 'desc']],
            columns: [
                { data: 'id', render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; }, orderable: false, width: '50px' },
                { data: 'pg_name', name: 'pg_name', orderable: false, searchable: false },
                { data: 'room_no', name: 'room_no', orderable: false, searchable: false },
                { data: 'checkin_date', name: 'checkin_date', render: function(data) { return window.erpDate ? window.erpDate(data) : (data || '-'); } },
                { data: 'monthly_rent', name: 'monthly_rent', render: function(data) { return data ? '₹' + data : '-'; } },
                { data: 'phone', name: 'phone' },
                { data: 'status', name: 'status', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : '-'; } },
                { data: 'action', name: 'action', orderable: false, sortable: false, width: '160px' }
            ]
        });

        $(document).on('click', '#filter_form .search', function() {
            table.ajax.reload();
        });

        $(document).on('click', '#filter_form .reset', function() {
            $('#filter_form')[0].reset();
            $('#filter_form').find('select').each(function () {
                if (this._erpSelectInst) this._erpSelectInst.setValue('');
            });
            table.ajax.reload();
        });
    });

    $(document).on('click', '.erp-inline-modal-close', function(e) {
        e.preventDefault();
        resetViewModal();
    });

    function resetViewModal() {
        $('#viewModal').addClass('hidden');
    }

    $(document).on('click', '.view', function(e) {
        e.preventDefault();
        var id = $(this).attr('data-id');
        var url = "{{ route('tenant.show', ':id') }}".replace(':id', id);
        $.ajax({
            type: "GET",
            url: url,
            dataType: 'json',
            success: function(response) {
                if (response.status_code == 200) {
                    var d = response.result;
                    $('#view_name').text(d.name || '-');
                    $('#view_email').text(d.email || '-');
                    $('#view_phone').text(d.phone || '-');
                    $('#view_pg').text(d.pg ? d.pg.pg_name : '-');
                    $('#view_room').text(d.room ? d.room.room_no : '-');
                    $('#view_bed_no').text(d.bed_no || '-');
                    $('#view_checkin').text(window.erpDate ? window.erpDate(d.checkin_date) : (d.checkin_date || '-'));
                    $('#view_checkout').text(window.erpDate ? window.erpDate(d.expected_checkout_date) : (d.expected_checkout_date || '-'));
                    $('#view_rent').text(d.monthly_rent ? '₹' + d.monthly_rent : '-');
                    $('#view_deposit').text(d.security_deposit ? '₹' + d.security_deposit : '-');
                    $('#view_payment_method').text(d.payment_method || '-');
                    $('#view_id_proof_type').text(d.id_proof_type || '-');
                    $('#view_id_proof_number').text(d.id_proof_number || '-');
                    $('#view_gender').text(d.gender || '-');
                    $('#view_dob').text(window.erpDate ? window.erpDate(d.date_of_birth) : (d.date_of_birth || '-'));
                    $('#view_occupation').text(d.occupation || '-');
                    $('#view_address').text(d.permanent_address || '-');
                    $('#view_notes').text(d.additional_notes || '-');
                    $('#view_status').text(d.status ? d.status.charAt(0).toUpperCase() + d.status.slice(1) : '-');
                    $('#view_created_at').text(window.erpDate ? window.erpDate(d.created_at) : (d.created_at || '-'));
                    $('#viewModal').removeClass('hidden');
                } else {
                    erpToast({ title: 'Error', message: response.message || 'Something went wrong', type: 'error' });
                }
            }
        });
    });
</script>
@endsection
