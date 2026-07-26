@extends('layouts.app-tw')
@section('title', __('payment::message.add'))
@section('nav-module', 'payment')
@section('breadcrumb', __('payment::message.add'))

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ __('payment::message.add') }}</h1>
        <p class="text-sm text-zinc-500 mt-1">{{ __('payment::message.make_payment') }}</p>
    </div>
    @can('payment-list')
    <a href="{{ route('payment.index') }}" class="h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center w-fit">
        <i class="fa-solid fa-arrow-left mr-1.5 text-xs"></i> {{ __('message.common.back') }}
    </a>
    @endcan
</div>

<form action="{{ route('payment.store') }}" method="POST" id="paymentForm" novalidate class="w-full">
    @csrf

    <div class="rounded-lg border border-zinc-200 bg-white shadow-sm overflow-hidden w-full">
        {{-- Header --}}
        <div class="flex items-center gap-2 px-4 py-1.5 border-b" style="background:#3D52A0; border-bottom-color:#324690;">
            <div class="h-6 w-6 rounded flex items-center justify-center" style="background:rgba(255,255,255,.18);">
                <i class="fa-solid fa-money-bill-wave text-white" style="font-size:11px;"></i>
            </div>
            <h2 class="text-sm font-semibold text-white">{{ __('payment::message.payment') }}</h2>
        </div>

        {{-- Server-side error banner --}}
        <div class="erp-form-error-banner rounded-lg border border-red-200 bg-red-50 p-3 mb-4 mx-6 mt-4" style="display:none;">
            <div class="flex items-start gap-2">
                <i class="fa-solid fa-circle-exclamation text-red-500 mt-0.5"></i>
                <p class="text-sm text-red-700 erp-form-error-text"></p>
            </div>
        </div>

        <div class="p-6 space-y-6">
            {{-- Row 1: PG, Room, Tenant --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('payment::message.pg') }} <span class="text-red-500">*</span></label>
                    <select name="pg_id" id="pg_id" required
                            data-fresh-prefetch="{{ route('lookup.pg-list') }}"
                            data-placeholder="— {{ __('payment::message.select_pg') }} —"
                            class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                        <option value=""></option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('payment::message.room_no') }} <span class="text-red-500">*</span></label>
                    <select name="room_id" id="room_id" required
                            data-placeholder="— {{ __('payment::message.select_room') }} —"
                            class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                        <option value=""></option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('payment::message.tenant') }} <span class="text-red-500">*</span></label>
                    <select name="tenant_id" id="tenant_id" required
                            data-fresh-prefetch="{{ route('lookup.tenant-list') }}"
                            data-placeholder="— {{ __('payment::message.select_tenant') }} —"
                            class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                        <option value=""></option>
                    </select>
                </div>
            </div>

            {{-- Row 2: Payment Date, Amount, Method --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('payment::message.payment_date') }} <span class="text-red-500">*</span></label>
                    <input type="date" name="payment_date" id="payment_date" required
                           class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                    <div class="mt-1 text-xs text-red-500 erp-field-error" id="error_payment_date"></div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('payment::message.amount') }} <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-400 text-sm">₹</span>
                        <input type="number" step="0.01" min="0" name="amount" id="amount" required
                               class="h-9 w-full rounded-md border border-zinc-200 bg-transparent pl-7 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                               placeholder="0.00">
                    </div>
                    <div class="mt-1 text-xs text-red-500 erp-field-error" id="error_amount"></div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('payment::message.payment_method') }} <span class="text-red-500">*</span></label>
                    <select name="payment_method" id="payment_method" required
                            class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                        <option value="">{{ __('message.common.select') }}</option>
                        <option value="Cash">{{ __('payment::message.cash') }}</option>
                        <option value="Bank Transfer">{{ __('payment::message.bank_transfer') }}</option>
                        <option value="Cheque">{{ __('payment::message.cheque') }}</option>
                        <option value="UPI">{{ __('payment::message.upi') }}</option>
                        <option value="Card">{{ __('payment::message.card') }}</option>
                        <option value="Other">{{ __('payment::message.other') }}</option>
                    </select>
                    <div class="mt-1 text-xs text-red-500 erp-field-error" id="error_payment_method"></div>
                </div>
            </div>

            {{-- Row 3: Reference --}}
            <div class="grid grid-cols-1 md:grid-cols-1 gap-4 sm:gap-6">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('payment::message.reference_no') }}</label>
                    <input type="text" name="reference_no" id="reference_no"
                           class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                           placeholder="{{ __('payment::message.enter_reference_no') }}">
                    <div class="mt-1 text-xs text-red-500 erp-field-error" id="error_reference_no"></div>
                </div>
            </div>

            {{-- Row 4: Remarks --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('payment::message.remarks') }}</label>
                <textarea name="remarks" id="remarks" rows="3"
                          class="w-full rounded-md border border-zinc-200 bg-transparent px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                          placeholder="{{ __('payment::message.enter_remarks') }}"></textarea>
                <div class="mt-1 text-xs text-red-500 erp-field-error" id="error_remarks"></div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-end gap-2 p-4 border-t border-zinc-200">
            @can('payment-list')
            <a href="{{ route('payment.index') }}" class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center">
                {{ __('message.common.cancel') }}
            </a>
            @endcan
            <button type="submit" id="save"
                    class="h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 inline-flex items-center">
                <i class="fa-solid fa-check mr-1.5 text-xs"></i>
                {{ __('message.common.submit') }}
            </button>
        </div>
    </div>
</form>
@endsection

@section('pagescript')
<script>
    'use strict';
    (function($) {
        var pgSelect = null;
        var roomSelect = null;
        var tenantSelect = null;

        function initPgSelect() {
            if (typeof erpSearchSelect === 'function') {
                pgSelect = erpSearchSelect('#pg_id', {
                    placeholder: '— {{ __("payment::message.select_pg") }} —',
                    freshPrefetch: '{{ route("lookup.pg-list") }}',
                });
            }
        }

        function initRoomSelect(pgId) {
            var url = '{{ route("lookup.rooms-by-pg") }}?pg_id=' + (pgId || 0);
            if (typeof erpSearchSelect === 'function') {
                if (roomSelect && typeof roomSelect.destroy === 'function') {
                    roomSelect.destroy();
                }
                roomSelect = erpSearchSelect('#room_id', {
                    placeholder: '— {{ __("payment::message.select_room") }} —',
                    freshPrefetch: url,
                });
            }
        }

        function initTenantSelect(pgId) {
            var url = '{{ route("lookup.tenant-list") }}' + (pgId ? '?pg_id=' + pgId : '');
            if (typeof erpSearchSelect === 'function') {
                if (tenantSelect && typeof tenantSelect.destroy === 'function') {
                    tenantSelect.destroy();
                }
                tenantSelect = erpSearchSelect('#tenant_id', {
                    placeholder: '— {{ __("payment::message.select_tenant") }} —',
                    freshPrefetch: url,
                });
            }
        }

        $(function() {
            initTenantSelect();
            initPgSelect();

            $(document).on('change', '#pg_id', function() {
                var val = $(this).val();
                if (val) {
                    initRoomSelect(val);
                    initTenantSelect(val);
                }
            });

            var initialPg = $('#pg_id').val();
            if (initialPg) {
                initRoomSelect(initialPg);
                initTenantSelect(initialPg);
            }
        });

        $('#paymentForm').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var button = form.find('#save');
            var originalHtml = button.html();

            button.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Processing...');

            $.ajax({
                type: 'POST',
                url: form.attr('action'),
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.status_code === 200) {
                        erpToast({ title: 'Success', message: response.message, type: 'success' });
                        window.location.href = '{{ route("payment.index") }}';
                    } else {
                        erpToast({ title: 'Error', message: response.message || 'Something went wrong', type: 'error' });
                        button.prop('disabled', false).html(originalHtml);
                    }
                },
                error: function(xhr) {
                    var errors = xhr.responseJSON;
                    if (errors && errors.errors) {
                        $('.erp-field-error').html('');
                        $('.erp-form-error-banner').hide();
                        $.each(errors.errors, function(field, messages) {
                            var errorEl = $('#error_' + field);
                            if (errorEl.length) {
                                errorEl.html(messages[0]);
                            }
                        });
                    } else {
                        erpToast({ title: 'Error', message: 'Something went wrong. Please try again.', type: 'error' });
                    }
                    button.prop('disabled', false).html(originalHtml);
                }
            });
        });
    })(jQuery);
</script>
@endsection
