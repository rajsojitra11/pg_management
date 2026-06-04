@extends('layouts.app-tw')
@section('title', __('currency::message.currency_master'))
@section('nav-module', 'currency')
@section('breadcrumb', 'Home > Masters > Currency')

@section('content')
{{-- Page Header --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ __('currency::message.list') }}</h1>
    </div>
    <div class="flex items-center gap-2">
        @can('currency-create')
        <button type="button" class="h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 whitespace-nowrap inline-flex items-center new-create" onclick="resetInlineModal();$('#inlineModal').removeClass('hidden')">
            <i class="fa-solid fa-plus mr-1.5 text-xs"></i> {{ __('message.common.addNew') }}
        </button>
        @endcan
    </div>
</div>

{{-- DataTable Card --}}
<div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
    <div class="p-4 overflow-x-auto">
        <table id="table" class="display responsive nowrap w-full">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('currency::message.name') }}</th>
                    <th>{{ __('currency::message.symbol') }}</th>
                    <th>{{ __('message.common.action') }}</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- Delete Modal — included globally in app-tw.blade.php, do NOT include here --}}

{{-- Add/Edit Modal --}}
<div id="inlineModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 erp-inline-modal-close"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="relative w-full max-w-sm rounded-lg border border-zinc-200 bg-white shadow-xl">
            {{-- Header --}}
            <div class="flex items-center justify-between p-4 border-b border-zinc-200">
                <h3 class="text-lg font-semibold text-zinc-900" id="exampleModalTitle">{{ __('currency::message.add') }}</h3>
                <button type="button" class="text-zinc-400 hover:text-zinc-600 erp-inline-modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            {{-- Body --}}
            <div id="body">
                <form id="form" action="javascript:void(0);" method="POST" novalidate>
                    @csrf
                    <div class="p-4 space-y-4">
                        {{-- Currency Name --}}
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1" for="currency_name">
                                {{ __('currency::message.name') }}<span class="text-red-500"> *</span>
                            </label>
                            <input type="hidden" name="id" id="id" value="">
                            <input type="text" required
                                   class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                   name="currency_name" id="currency_name"
                                   placeholder="{{ __('currency::message.name') }}">
                            <div class="mt-1 text-sm text-red-500" id="error_currency_name"></div>
                        </div>

                        {{-- Currency Symbol --}}
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1" for="currency_symbol">
                                {{ __('currency::message.symbol') }}<span class="text-red-500"> *</span>
                            </label>
                            <input type="text" required maxlength="10"
                                   class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                   name="currency_symbol" id="currency_symbol"
                                   placeholder="{{ __('currency::message.enter_symbol') }}">
                            <div class="mt-1 text-sm text-red-500" id="error_currency_symbol"></div>
                        </div>


                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-end gap-2 p-4 border-t border-zinc-200">
                        <button type="button" class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center erp-inline-modal-close">
                            {{ __('message.common.cancel') }}
                        </button>
                        <button type="button" id="save" data-route="{{ route('currency.store') }}"
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
@endsection

@section('pagescript')
{{-- delete.js and save.js/update.js are loaded globally in app-tw.blade.php --}}
{{-- validation-loader.js NOT needed — delete.js handles its own validation --}}

<script type="application/javascript">
    'use strict';
    window.URL_ROUTE = "{{ route('currency.index') }}";

    // Validation messages used by validateFormFields() in save.js/update.js and delete.js
    window.validationMessages = {
    };

    var table = '';
    $(function() {
        table = initErpTable('#table', {
            ajax: window.URL_ROUTE,
            processing: true,
            serverSide: true,
            scrollX: true,
            aLengthMenu: [
                [15, 30, 50, 100, -1],
                [15, 30, 50, 100, "All"]
            ],
            order: [[0, 'desc']],
            columns: [
                {
                    data: 'id',
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    },
                    orderable: false,
                    width: '50px'
                },
                { data: 'currency_name', name: 'currency_name' },
                { data: 'currency_symbol', name: 'currency_symbol' },
                { data: 'action', name: 'action', orderable: false, sortable: false, width: '160px' }
            ]
        });
    });

    // ── Modal close/reset ──────────────────────────
    function resetInlineModal() {
        $('#inlineModal').addClass('hidden');
        $('#form')[0].reset();
        $('#form').find('.border-red-500').removeClass('border-red-500');
        $('#form').find('.erp-field-error').remove();
        $('#form').find('.erp-form-error-banner').hide();
        $('#error_currency_name').html('');
        $('#error_currency_symbol').html('');
        $('#inlineModal').find('.erp-btn-locked').each(function() {
            $(this).css({ opacity: '', pointerEvents: '' }).removeClass('erp-btn-locked').removeData('erp-original-pointer');
        });
        $("#save").attr('data-route', "{{ route('currency.store') }}")
            .removeClass('update').addClass('save')
            .html('<i class="fa-solid fa-check mr-1.5 text-xs"></i> {{ __("message.common.submit") }}')
            .prop('disabled', false)
            .removeAttr('style')
            .removeData('erp-original-html')
            .removeData('erp-original-style');
        $("#exampleModalTitle").html("{{ __('currency::message.add') }}");

    }

    $(document).on('click', '.erp-inline-modal-close', function(e) {
        e.preventDefault();
        resetInlineModal();
    });

    // ── Edit button handler ────────────────────────
    $(document).on('click', '.edit', function(e) {
        e.preventDefault();
        resetInlineModal();
        $("#save").attr('data-route', '').removeClass('save').addClass('update');
        var id = $(this).attr('data-id');
        var url = "{{ route('currency.edit', ':id') }}".replace(':id', id);
        $("#save").attr('data-route', "{{ route('currency.update', ':id') }}".replace(':id', id));
        $.ajax({
            type: "GET",
            url: url,
            dataType: 'json',
            success: function(response) {
                if (response.status_code == 200) {
                    $("#exampleModalTitle").html("{{ __('currency::message.edit') }}");
                    $("#currency_name").val(response.result.currency_name);
                    $("#currency_symbol").val(response.result.currency_symbol);
                    $("#id").val(id);
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
