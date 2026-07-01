@extends('layouts.app-tw')
@section('title', __('payment::message.payment_master'))
@section('nav-module', 'payment')
@section('breadcrumb', 'Home > Payment')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ __('payment::message.list') }}</h1>
    </div>
    <div class="flex items-center gap-2">
        @can('payment-create')
        <a href="{{ route('payment.create') }}" class="h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 whitespace-nowrap inline-flex items-center">
            <i class="fa-solid fa-plus mr-1.5 text-xs"></i> {{ __('message.common.addNew') }}
        </a>
        @endcan
    </div>
</div>

{{-- Tab Navigation --}}
<div class="mb-4 border-b border-zinc-200">
    <nav class="flex gap-1 -mb-px" id="paymentTabs">
        <button type="button" class="tab-link px-4 py-2.5 text-sm font-medium border-b-2 transition-colors"
                data-tab="all">
            {{ __('payment::message.tab_all') }}
        </button>
        <button type="button" class="tab-link px-4 py-2.5 text-sm font-medium border-b-2 transition-colors"
                data-tab="verified">
            {{ __('payment::message.tab_verified') }}
        </button>
        <button type="button" class="tab-link px-4 py-2.5 text-sm font-medium border-b-2 transition-colors"
                data-tab="pending">
            {{ __('payment::message.tab_pending_verification') }}
        </button>
        <button type="button" class="tab-link px-4 py-2.5 text-sm font-medium border-b-2 transition-colors"
                data-tab="overdue">
            {{ __('payment::message.tab_pending_payment') }}
        </button>
    </nav>
</div>

<div id="paymentSection">
{{-- Filter Bar --}}
<form id="filter_form" class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm mb-4" onsubmit="return false;">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 lg:items-end">
        <div class="lg:col-span-7">
            <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('message.common.search') }}</label>
            <div class="flex h-9 rounded-md border border-zinc-200 bg-white focus-within:ring-2 focus-within:ring-zinc-900 focus-within:ring-offset-2 overflow-hidden">
                <span class="inline-flex items-center px-3 bg-zinc-50 border-r border-zinc-200 text-zinc-400 text-xs"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" id="filterSearch" name="filter_search" placeholder="{{ __('payment::message.search_placeholder') }}" class="flex-1 min-w-0 bg-transparent px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:outline-none">
            <input type="hidden" id="filterTenantId" name="filter_tenant_id" value="{{ request('filter_tenant_id') }}">
            <input type="hidden" id="filterVerified" name="filter_verified" value="{{ request('filter_verified') }}">
            </div>
        </div>
        <div class="lg:col-span-5 flex items-center gap-2 justify-end">
            <button type="button" class="search h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800">{{ __('payment::message.apply') }}</button>
            <button type="button" class="reset h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm text-zinc-500 hover:bg-zinc-50">{{ __('payment::message.reset') }}</button>
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
                    <th>{{ __('payment::message.tenant') }}</th>
                    <th>{{ __('payment::message.pg') }}</th>
                    <th>{{ __('payment::message.room_no') }}</th>
                    <th>{{ __('payment::message.payment_date') }}</th>
                    <th>{{ __('payment::message.amount') }}</th>
                    <th>{{ __('payment::message.payment_method') }}</th>
                    <th>{{ __('payment::message.verified') }}</th>
                    <th>{{ __('message.common.action') }}</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
</div>

{{-- Pending Payment Section --}}
<div id="pendingSection" class="hidden">
    <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
        <div class="p-4 overflow-x-auto">
            <table id="pendingTable" class="display responsive nowrap w-full">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('payment::message.tenant') }}</th>
                        <th>{{ __('payment::message.pg') }}</th>
                        <th>{{ __('payment::message.room_no') }}</th>
                        <th>{{ __('tenant::message.checkin_date') }}</th>
                        <th>{{ __('payment::message.monthly_rent') }}</th>
                        <th>{{ __('payment::message.days_elapsed') }}</th>
                        <th>{{ __('message.common.action') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

{{-- View Modal --}}
<div id="viewModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 erp-inline-modal-close"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="relative w-full max-w-2xl rounded-lg border border-zinc-200 bg-white shadow-xl">
            <div class="flex items-center justify-between p-4 border-b border-zinc-200">
                <h3 class="text-lg font-semibold text-zinc-900" id="viewModalTitle">{{ __('payment::message.payment') }}</h3>
                <button type="button" class="text-zinc-400 hover:text-zinc-600 erp-inline-modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-5 space-y-4 max-h-[70vh] overflow-y-auto">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('payment::message.tenant') }}</p>
                        <p class="text-sm font-semibold text-zinc-900" id="view_tenant">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('payment::message.pg') }}</p>
                        <p class="text-sm text-zinc-900" id="view_pg">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('payment::message.room_no') }}</p>
                        <p class="text-sm text-zinc-900" id="view_room">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('payment::message.payment_date') }}</p>
                        <p class="text-sm text-zinc-900" id="view_payment_date">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('payment::message.amount') }}</p>
                        <p class="text-sm text-zinc-900" id="view_amount">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('payment::message.payment_method') }}</p>
                        <p class="text-sm text-zinc-900" id="view_payment_method">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('payment::message.reference_no') }}</p>
                        <p class="text-sm text-zinc-900" id="view_reference_no">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('payment::message.verified') }}</p>
                        <p class="text-sm" id="view_status">-</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('payment::message.remarks') }}</p>
                        <p class="text-sm text-zinc-900" id="view_remarks">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('message.common.created_at') }}</p>
                        <p class="text-sm text-zinc-900" id="view_created_at">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('message.common.updated_at') }}</p>
                        <p class="text-sm text-zinc-900" id="view_updated_at">-</p>
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
    window.URL_ROUTE = "{{ route('payment.index') }}";

    var table = '';
    var pendingTable = '';

    function getUrlParam(name) {
        var params = new URLSearchParams(window.location.search);
        return params.get(name);
    }

    function setUrlParam(name, value) {
        var url = new URL(window.location.href);
        if (value) {
            url.searchParams.set(name, value);
        } else {
            url.searchParams.delete(name);
        }
        window.history.replaceState({}, '', url);
    }

    function switchTab(tab) {
        setUrlParam('tab', tab !== 'all' ? tab : '');

        $('#paymentTabs .tab-link').removeClass('border-zinc-900 text-zinc-900').addClass('border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300');
        $('#paymentTabs .tab-link[data-tab="' + tab + '"]').removeClass('border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300').addClass('border-zinc-900 text-zinc-900');

        if (tab === 'overdue') {
            $('#paymentSection').addClass('hidden');
            $('#pendingSection').removeClass('hidden');
            if (!pendingTable || !$.fn.DataTable.isDataTable('#pendingTable')) {
                initPendingTable();
            }
        } else {
            $('#paymentSection').removeClass('hidden');
            $('#pendingSection').addClass('hidden');

            if (tab === 'verified') {
                $('#filterVerified').val('verified');
            } else if (tab === 'pending') {
                $('#filterVerified').val('pending');
            } else {
                $('#filterVerified').val('');
            }

            if (table && $.fn.DataTable.isDataTable('#table')) {
                table.ajax.reload();
            }
        }
    }

    function initPendingTable() {
        pendingTable = initErpTable('#pendingTable', {
            ajax: {
                url: '{{ route("payment.pending.data") }}',
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
                { data: 'name', name: 'name' },
                { data: 'pg_name', name: 'pg_name', orderable: false, searchable: false },
                { data: 'room_no', name: 'room_no', orderable: false, searchable: false },
                { data: 'checkin_date', name: 'checkin_date', render: function(data) { return window.erpDate ? window.erpDate(data) : (data || '-'); } },
                { data: 'monthly_rent', name: 'monthly_rent', render: function(data) { return data ? '₹' + parseFloat(data).toFixed(2) : '-'; } },
                { data: 'days_elapsed', name: 'days_elapsed', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, sortable: false, width: '160px' }
            ]
        });
    }

    $(function() {
        table = initErpTable('#table', {
            ajax: {
                url: window.URL_ROUTE,
                data: function (d) {
                    d.filter_search = $('#filterSearch').val();
                    d.filter_verified = $('#filterVerified').val();
                    d.filter_tenant_id = $('#filterTenantId').val();
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
                { data: 'tenant_name', name: 'tenant_name', orderable: false, searchable: false },
                { data: 'pg_name', name: 'pg_name', orderable: false, searchable: false },
                { data: 'room_no', name: 'room_no', orderable: false, searchable: false },
                { data: 'payment_date', name: 'payment_date', render: function(data) { return window.erpDate ? window.erpDate(data) : (data || '-'); } },
                { data: 'amount', name: 'amount', render: function(data) { return data ? '₹' + parseFloat(data).toFixed(2) : '-'; } },
                { data: 'payment_method', name: 'payment_method', render: function(data) { return data || '-'; } },
                { data: 'verified', name: 'verified' },
                { data: 'action', name: 'action', orderable: false, sortable: false, width: '160px' }
            ]
        });

        var initialTab = getUrlParam('tab') || 'all';
        switchTab(initialTab);

        $(document).on('click', '#paymentTabs .tab-link', function() {
            var tab = $(this).data('tab');
            switchTab(tab);
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

        $(document).on('click', '.verified-toggle', function(e) {
            e.preventDefault();
            var checkbox = $(this);
            var url = checkbox.data('url');
            var label = checkbox.closest('label');
            var currentlyVerified = label.hasClass('bg-green-100');
            var newStatusLabel = currentlyVerified ? '{{ __("payment::message.verified_pending") }}' : '{{ __("payment::message.verified_done") }}';

            erpConfirm({
                title: '{{ __("payment::message.verified") }}',
                message: '{{ __("payment::message.confirm_verified") }}'.replace(':status', newStatusLabel.toLowerCase()),
                confirmText: '{{ __("message.common.yes") }}',
            }).then(function(confirmed) {
                if (!confirmed) return;

                checkbox.prop('disabled', true);
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status_code == 200) {
                            if (res.verified === 'verified') {
                                label.removeClass('bg-amber-100 text-amber-800 border-amber-200')
                                    .addClass('bg-green-100 text-green-800 border-green-200');
                                checkbox.prop('checked', true);
                                label.contents().last().replaceWith(' Verified');
                            } else {
                                label.removeClass('bg-green-100 text-green-800 border-green-200')
                                    .addClass('bg-amber-100 text-amber-800 border-amber-200');
                                checkbox.prop('checked', false);
                                label.contents().last().replaceWith(' Pending');
                            }
                            erpToast({ title: 'Success', message: res.message, type: 'success' });
                        }
                        checkbox.prop('disabled', false);
                    },
                    error: function() {
                        checkbox.prop('disabled', false);
                        erpToast({ title: 'Error', message: 'Failed to update verification status.', type: 'error' });
                    }
                });
            });
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
        var url = "{{ route('payment.show', ':id') }}".replace(':id', id);
        $.ajax({
            type: "GET",
            url: url,
            dataType: 'json',
            success: function(response) {
                if (response.status_code == 200) {
                    var d = response.result;
                    $('#view_tenant').text(d.tenant ? d.tenant.name : '-');
                    $('#view_pg').text(d.pg ? d.pg.pg_name : '-');
                    $('#view_room').text(d.room ? d.room.room_no : '-');
                    $('#view_payment_date').text(window.erpDate ? window.erpDate(d.payment_date) : (d.payment_date || '-'));
                    $('#view_amount').text(d.amount ? '₹' + parseFloat(d.amount).toFixed(2) : '-');
                    $('#view_payment_method').text(d.payment_method || '-');
                    $('#view_reference_no').text(d.reference_no || '-');
                    if (d.verified === 'verified') {
                        $('#view_status').html('<span class="inline-flex items-center rounded-md bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 border border-green-200">Verified</span>');
                    } else {
                        $('#view_status').html('<span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 border border-amber-200">Pending</span>');
                    }
                    $('#view_remarks').text(d.remarks || '-');
                    $('#view_created_at').text(window.erpDate ? window.erpDate(d.created_at) : (d.created_at || '-'));
                    $('#view_updated_at').text(window.erpDate ? window.erpDate(d.updated_at) : (d.updated_at || '-'));
                    $('#viewModal').removeClass('hidden');
                } else {
                    erpToast({ title: 'Error', message: response.message || 'Something went wrong', type: 'error' });
                }
            }
        });
    });
</script>
@endsection
