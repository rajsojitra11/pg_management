@extends('layouts.app-tw')
@section('title', __('subscription::message.subscription_master'))
@section('nav-module', 'subscription')
@section('breadcrumb', 'Home > Subscription')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ __('subscription::message.list') }}</h1>
    </div>
    <div class="flex items-center gap-2">
        @can('subscription-create')
        <button type="button" class="h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 whitespace-nowrap inline-flex items-center new-create" onclick="resetInlineModal();$('#inlineModal').removeClass('hidden')">
            <i class="fa-solid fa-plus mr-1.5 text-xs"></i> {{ __('message.common.addNew') }}
        </button>
        @endcan
    </div>
</div>

{{-- Filter Bar --}}
<form id="filter_form" class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm mb-4" onsubmit="return false;">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 lg:items-end">
        <div class="lg:col-span-4">
            <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('message.common.search') }}</label>
            <div class="flex h-9 rounded-md border border-zinc-200 bg-white focus-within:ring-2 focus-within:ring-zinc-900 focus-within:ring-offset-2 overflow-hidden">
                <span class="inline-flex items-center px-3 bg-zinc-50 border-r border-zinc-200 text-zinc-400 text-xs"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" id="filterSearch" name="filter_search" placeholder="{{ __('subscription::message.search_placeholder') }}" class="flex-1 min-w-0 bg-transparent px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:outline-none">
            </div>
        </div>
        <div class="lg:col-span-3">
            <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('subscription::message.status') }}</label>
            <select id="filterStatus" name="filter_status" class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500">
                <option value="">{{ __('message.common.select') }}</option>
                <option value="active">{{ __('subscription::message.status_active') }}</option>
                <option value="expired">{{ __('subscription::message.status_expired') }}</option>
                <option value="cancelled">{{ __('subscription::message.status_cancelled') }}</option>
                <option value="pending">{{ __('subscription::message.status_pending') }}</option>
            </select>
        </div>
        <div class="lg:col-span-3">
            <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('subscription::message.payment_status') }}</label>
            <select id="filterPaymentStatus" name="filter_payment_status" class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500">
                <option value="">{{ __('message.common.select') }}</option>
                <option value="paid">{{ __('subscription::message.payment_status_paid') }}</option>
                <option value="unpaid">{{ __('subscription::message.payment_status_unpaid') }}</option>
                <option value="pending">{{ __('subscription::message.payment_status_pending') }}</option>
            </select>
        </div>
        <div class="lg:col-span-2 flex items-center gap-2 justify-end">
            <button type="button" class="search h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800">{{ __('subscription::message.apply') }}</button>
            <button type="button" class="reset h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm text-zinc-500 hover:bg-zinc-50">{{ __('subscription::message.reset') }}</button>
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
                    <th>{{ __('subscription::message.subscriber_name') }}</th>
                    <th>{{ __('subscription::message.email') }}</th>
                    <th>{{ __('subscription::message.start_date') }}</th>
                    <th>{{ __('subscription::message.end_date') }}</th>
                    <th>{{ __('subscription::message.status') }}</th>
                    <th>{{ __('subscription::message.payment_status') }}</th>
                    <th>{{ __('message.common.action') }}</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<div id="inlineModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 erp-inline-modal-close"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="relative w-full max-w-2xl rounded-lg border border-zinc-200 bg-white shadow-xl">
            <div class="flex items-center justify-between p-4 border-b border-zinc-200">
                <h3 class="text-lg font-semibold text-zinc-900" id="exampleModalTitle">{{ __('subscription::message.add') }}</h3>
                <button type="button" class="text-zinc-400 hover:text-zinc-600 erp-inline-modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div id="body">
                <form id="form" action="javascript:void(0);" method="POST" novalidate>
                    @csrf
                    <div class="p-4 space-y-4">
                        <input type="hidden" name="id" id="id" value="">

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="subscriber_name">
                                    {{ __('subscription::message.subscriber_name') }}<span class="text-red-500"> *</span>
                                </label>
                                <input type="text" required
                                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                       name="subscriber_name" id="subscriber_name"
                                       placeholder="{{ __('subscription::message.enter_name') }}">
                                <div class="mt-1 text-sm text-red-500" id="error_subscriber_name"></div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="email">
                                    {{ __('subscription::message.email') }}<span class="text-red-500"> *</span>
                                </label>
                                <select name="email" id="email" style="width:100%;" required>
                                    <option value="">{{ __('message.common.select') }}</option>
                                    @foreach ($pgAdminUsers as $user)
                                    <option value="{{ $user->email }}">{{ $user->email }}</option>
                                    @endforeach
                                </select>
                                <div class="mt-1 text-sm text-red-500" id="error_email"></div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1" for="phone">
                                {{ __('subscription::message.phone') }}<span class="text-red-500"> *</span>
                            </label>
                            <input type="text" required
                                   class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                   name="phone" id="phone"
                                   placeholder="{{ __('subscription::message.enter_phone') }}">
                            <div class="mt-1 text-sm text-red-500" id="error_phone"></div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="plan_type">
                                    {{ __('subscription::message.plan_type') }}<span class="text-red-500"> *</span>
                                </label>
                                <select required
                                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                       name="plan_type" id="plan_type">
                                    <option value="">{{ __('message.common.select') }}</option>
                                    <option value="basic">Basic</option>
                                    <option value="premium">Premium</option>
                                    <option value="enterprise">Enterprise</option>
                                </select>
                                <div class="mt-1 text-sm text-red-500" id="error_plan_type"></div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="start_date">
                                    {{ __('subscription::message.start_date') }}<span class="text-red-500"> *</span>
                                </label>
                                <input type="text" required autocomplete="off"
                                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500 flatpickr-datetime"
                                       name="start_date" id="start_date"
                                       placeholder="{{ __('subscription::message.enter_start_date') }}">
                                <div class="mt-1 text-sm text-red-500" id="error_start_date"></div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="end_date">
                                    {{ __('subscription::message.end_date') }}<span class="text-red-500"> *</span>
                                </label>
                                <input type="text" required autocomplete="off"
                                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500 flatpickr-datetime"
                                       name="end_date" id="end_date"
                                       placeholder="{{ __('subscription::message.enter_end_date') }}">
                                <div class="mt-1 text-sm text-red-500" id="error_end_date"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="status">
                                    {{ __('subscription::message.status') }}<span class="text-red-500"> *</span>
                                </label>
                                <select required
                                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                       name="status" id="status">
                                    <option value="active">Active</option>
                                    <option value="expired">Expired</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="pending">Pending</option>
                                </select>
                                <div class="mt-1 text-sm text-red-500" id="error_status"></div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="amount">
                                    {{ __('subscription::message.amount') }}<span class="text-red-500"> *</span>
                                </label>
                                <input type="number" step="0.01" min="0" required
                                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                       name="amount" id="amount"
                                       placeholder="{{ __('subscription::message.enter_amount') }}">
                                <div class="mt-1 text-sm text-red-500" id="error_amount"></div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="payment_status">
                                    {{ __('subscription::message.payment_status') }}<span class="text-red-500"> *</span>
                                </label>
                                <select required
                                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                       name="payment_status" id="payment_status">
                                    <option value="paid">Paid</option>
                                    <option value="unpaid">Unpaid</option>
                                    <option value="pending">Pending</option>
                                </select>
                                <div class="mt-1 text-sm text-red-500" id="error_payment_status"></div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 p-4 border-t border-zinc-200">
                        <button type="button" class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center erp-inline-modal-close">
                            {{ __('message.common.cancel') }}
                        </button>
                        <button type="button" id="save" data-route="{{ route('subscription.store') }}"
                                class="h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 whitespace-nowrap inline-flex items-center save">
                            <i class="fa-solid fa-check mr-1.5 text-xs"></i>
                            {{ __('message.common.submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div id="viewModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 erp-inline-modal-close"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="relative w-full max-w-2xl rounded-lg border border-zinc-200 bg-white shadow-xl">
            <div class="flex items-center justify-between p-4 border-b border-zinc-200">
                <h3 class="text-lg font-semibold text-zinc-900" id="viewModalTitle">{{ __('subscription::message.subscription') }}</h3>
                <button type="button" class="text-zinc-400 hover:text-zinc-600 erp-inline-modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-5 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('subscription::message.subscriber_name') }}</p>
                        <p class="text-sm font-semibold text-zinc-900" id="view_subscriber_name">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('subscription::message.email') }}</p>
                        <p class="text-sm text-zinc-900" id="view_email">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('subscription::message.phone') }}</p>
                        <p class="text-sm text-zinc-900" id="view_phone">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('subscription::message.plan_type') }}</p>
                        <p class="text-sm text-zinc-900" id="view_plan_type">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('subscription::message.start_date') }}</p>
                        <p class="text-sm text-zinc-900" id="view_start_date">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('subscription::message.end_date') }}</p>
                        <p class="text-sm text-zinc-900" id="view_end_date">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('subscription::message.status') }}</p>
                        <p class="text-sm" id="view_status">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('subscription::message.amount') }}</p>
                        <p class="text-sm text-zinc-900" id="view_amount">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('subscription::message.payment_status') }}</p>
                        <p class="text-sm text-zinc-900" id="view_payment_status">-</p>
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
<script type="application/javascript">
    'use strict';
    window.URL_ROUTE = "{{ route('subscription.index') }}";

    window.validationMessages = {};

    var table = '';
    var emailSelectInst = null;
    var startDatePicker = null;
    var endDatePicker = null;

    $(function() {
        table = initErpTable('#table', {
            ajax: {
                url: window.URL_ROUTE,
                data: function (d) {
                    d.filter_search = $('#filterSearch').val();
                    d.filter_status = $('#filterStatus').val();
                    d.filter_payment_status = $('#filterPaymentStatus').val();
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
                { data: 'subscriber_name', name: 'subscriber_name' },
                { data: 'email', name: 'email' },
                { data: 'start_date', name: 'start_date', render: function(data) { return window.erpDate ? window.erpDate(data) : (data || '-'); } },
                { data: 'end_date', name: 'end_date', render: function(data) { return window.erpDate ? window.erpDate(data) : (data || '-'); } },
                { data: 'status', name: 'status' },
                { data: 'payment_status', name: 'payment_status' },
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

        if (typeof initErpSelect === 'function') {
            initErpSelect('#filterPaymentStatus', { allowClear: true, placeholder: '{{ __("message.common.select") }}' });
        }

        if (typeof erpSearchSelect === 'function') {
            var emailOptions = {!! json_encode($pgAdminUsers->map(function($u) { return ['value' => $u->email, 'label' => $u->email]; })->values()) !!};
            emailSelectInst = erpSearchSelect('#email', {
                placeholder: '{{ __("subscription::message.enter_email") }}',
                options: emailOptions
            });
        }

        if (typeof flatpickr === 'function') {
            if ($('#start_date').length) {
                startDatePicker = flatpickr('#start_date', {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd-m-Y',
                    allowInput: true
                });
            }
            if ($('#end_date').length) {
                endDatePicker = flatpickr('#end_date', {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd-m-Y',
                    allowInput: true
                });
            }
        }
    });

    function resetInlineModal() {
        $('#inlineModal').addClass('hidden');
        $('#form')[0].reset();
        if (startDatePicker) {
            startDatePicker.clear();
        }
        if (endDatePicker) {
            endDatePicker.clear();
        }
        $('#form').find('.border-red-500').removeClass('border-red-500');
        $('#form').find('.erp-field-error').remove();
        $('#form').find('.erp-form-error-banner').hide();
        $('#error_subscriber_name').html('');
        $('#error_email').html('');
        $('#error_phone').html('');
        $('#error_plan_type').html('');
        $('#error_start_date').html('');
        $('#error_end_date').html('');
        $('#error_status').html('');
        $('#error_amount').html('');
        $('#error_payment_status').html('');
        $('#inlineModal').find('.erp-btn-locked').each(function() {
            $(this).css({ opacity: '', pointerEvents: '' }).removeClass('erp-btn-locked').removeData('erp-original-pointer');
        });
        $("#save").attr('data-route', "{{ route('subscription.store') }}")
            .removeClass('update').addClass('save')
            .html('<i class="fa-solid fa-check mr-1.5 text-xs"></i> {{ __("message.common.submit") }}')
            .prop('disabled', false)
            .removeAttr('style')
            .removeData('erp-original-html')
            .removeData('erp-original-style');
        $("#exampleModalTitle").html("{{ __('subscription::message.add') }}");

        if (emailSelectInst && typeof emailSelectInst.setValue === 'function') {
            emailSelectInst.setValue('');
        }
    }

    $(document).on('click', '.erp-inline-modal-close', function(e) {
        e.preventDefault();
        resetInlineModal();
        resetViewModal();
    });

    function resetViewModal() {
        $('#viewModal').addClass('hidden');
    }

    $(document).on('click', '.view', function(e) {
        e.preventDefault();
        var id = $(this).attr('data-id');
        var url = "{{ route('subscription.show', ':id') }}".replace(':id', id);
        $.ajax({
            type: "GET",
            url: url,
            dataType: 'json',
            success: function(response) {
                if (response.status_code == 200) {
                    var d = response.result;
                    $('#view_subscriber_name').text(d.subscriber_name || '-');
                    $('#view_email').text(d.email || '-');
                    $('#view_phone').text(d.phone || '-');
                    $('#view_plan_type').text(d.plan_type ? d.plan_type.charAt(0).toUpperCase() + d.plan_type.slice(1) : '-');
                    $('#view_start_date').text(window.erpDate ? window.erpDate(d.start_date) : (d.start_date || '-'));
                    $('#view_end_date').text(window.erpDate ? window.erpDate(d.end_date) : (d.end_date || '-'));
                    if (d.status === 'active') {
                        $('#view_status').html('<span class="inline-flex items-center rounded-md bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 border border-green-200">Active</span>');
                    } else {
                        $('#view_status').text(d.status ? d.status.charAt(0).toUpperCase() + d.status.slice(1) : '-');
                    }
                    $('#view_amount').text(d.amount ? '$' + parseFloat(d.amount).toFixed(2) : '-');
                    $('#view_payment_status').text(d.payment_status ? d.payment_status.charAt(0).toUpperCase() + d.payment_status.slice(1) : '-');
                    $('#view_created_at').text(window.erpDate ? window.erpDate(d.created_at) : (d.created_at || '-'));
                    $('#view_updated_at').text(window.erpDate ? window.erpDate(d.updated_at) : (d.updated_at || '-'));
                    $('#viewModal').removeClass('hidden');
                } else if (response.status_code == 201 || response.status_code == 404) {
                    toastr.warning(response.message, "Warning");
                } else {
                    toastr.error(response.message, "Error");
                }
            }
        });
    });

    $(document).on('click', '.edit', function(e) {
        e.preventDefault();
        resetInlineModal();
        $("#save").attr('data-route', '').removeClass('save').addClass('update');
        var id = $(this).attr('data-id');
        var url = "{{ route('subscription.edit', ':id') }}".replace(':id', id);
        $("#save").attr('data-route', "{{ route('subscription.update', ':id') }}".replace(':id', id));
        $.ajax({
            type: "GET",
            url: url,
            dataType: 'json',
            success: function(response) {
                if (response.status_code == 200) {
                    $("#exampleModalTitle").html("{{ __('subscription::message.edit') }}");
                    $("#subscriber_name").val(response.result.subscriber_name);
                    $("#phone").val(response.result.phone);
                    $("#plan_type").val(response.result.plan_type);
                    if (startDatePicker) {
                        startDatePicker.setDate(response.result.start_date || '', true);
                    } else {
                        $("#start_date").val(response.result.start_date || '');
                    }
                    if (endDatePicker) {
                        endDatePicker.setDate(response.result.end_date || '', true);
                    } else {
                        $("#end_date").val(response.result.end_date || '');
                    }
                    $("#status").val(response.result.status);
                    $("#amount").val(response.result.amount);
                    $("#payment_status").val(response.result.payment_status);
                    $("#id").val(id);

                    if (emailSelectInst && typeof emailSelectInst.setValue === 'function') {
                        emailSelectInst.setValue(response.result.email || '');
                    } else {
                        $("#email").val(response.result.email);
                    }

                    $('#inlineModal').removeClass('hidden');
                } else if (response.status_code == 201 || response.status_code == 404) {
                    toastr.warning(response.message, "Warning");
                } else {
                    toastr.error(response.message, "Error");
                }
            }
        });
    });
</script>
@endsection
