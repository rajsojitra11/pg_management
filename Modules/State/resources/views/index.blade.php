@extends('layouts.app-tw')
@section('title', __('state::message.list'))
@section('nav-module', 'state')
@section('breadcrumb', 'Home > Masters > State')

@section('content')
{{-- Page Header --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ __('state::message.list') }}</h1>
    </div>
    <div class="flex items-center gap-2">
        @can('state-create')
        <button type="button" class="h-9 px-4 rounded-md text-sm font-medium whitespace-nowrap inline-flex items-center" style="background-color: var(--erp-primary); color: var(--erp-primary-fg);" onclick="resetInlineModal();$('#inlineModal').removeClass('hidden')">
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
                    <th>{{ __('state::message.country') }}</th>
                    <th>{{ __('state::message.name') }}</th>
                    <th>{{ __('state::message.code') }}</th>
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
        <div class="relative w-full max-w-md rounded-lg border bg-white shadow-xl" style="border-color: var(--erp-border); background-color: var(--erp-bg);">
            {{-- Header --}}
            <div class="flex items-center justify-between p-4 border-b" style="border-color: var(--erp-border);">
                <h3 class="text-lg font-semibold" id="modalTitle" style="color: var(--erp-text);">{{ __('state::message.add') }}</h3>
                <button type="button" class="text-zinc-400 hover:text-zinc-600 erp-inline-modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            {{-- Body --}}
            <div id="body">
                <form id="form" action="javascript:void(0);" method="POST" novalidate>
                    @csrf
                    <input type="hidden" name="id" id="id" value="">
                    <div class="p-4 space-y-4">

                        {{-- Country --}}
                        <div>
                            <label class="block text-sm font-medium mb-1" for="country_id" style="color: var(--erp-text);">
                                {{ __('state::message.country') }}<span class="text-red-500"> *</span>
                            </label>
                            <select id="country_id" name="country_id" required
                                    class="select-searchable w-full rounded-md border px-3 py-2 text-sm"
                                    style="border-color: var(--erp-border); background-color: var(--erp-bg); color: var(--erp-text);">
                                <option value="">{{ __('message.common.select') }}</option>
                                @foreach (\Modules\Country\Models\Country::orderBy('name')->get(['id', 'name']) as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <div class="mt-1 text-sm text-red-500" id="error_country_id"></div>
                        </div>

                        {{-- State Name --}}
                        <div>
                            <label class="block text-sm font-medium mb-1" for="name" style="color: var(--erp-text);">
                                {{ __('state::message.name') }}<span class="text-red-500"> *</span>
                            </label>
                            <input type="text" required
                                   class="w-full rounded-md border px-3 py-2 text-sm focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500"
                                   style="border-color: var(--erp-border); background-color: var(--erp-bg); color: var(--erp-text); --tw-ring-color: var(--erp-primary);"
                                   name="name" id="name"
                                   placeholder="{{ __('state::message.enter_name') }}">
                            <div class="mt-1 text-sm text-red-500" id="error_name"></div>
                        </div>

                        {{-- State Code --}}
                        <div>
                            <label class="block text-sm font-medium mb-1" for="code" style="color: var(--erp-text);">
                                {{ __('state::message.code') }}
                            </label>
                            <input type="text" maxlength="10"
                                   class="w-full rounded-md border px-3 py-2 text-sm focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500"
                                   style="border-color: var(--erp-border); background-color: var(--erp-bg); color: var(--erp-text); --tw-ring-color: var(--erp-primary);"
                                   name="code" id="code"
                                   placeholder="{{ __('state::message.code') }}">
                            <div class="mt-1 text-sm text-red-500" id="error_code"></div>
                        </div>


                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-end gap-2 p-4 border-t" style="border-color: var(--erp-border);">
                        <button type="button" class="erp-modal-btn-secondary erp-inline-modal-close">
                            <i class="fa-solid fa-xmark mr-1.5 text-xs"></i> {{ __('message.common.cancel') }}
                        </button>
                        <button type="button" id="save" data-route="{{ route('state.store') }}"
                                class="erp-modal-btn-primary save">
                            <i class="fa-solid fa-check mr-1.5 text-xs"></i> {{ __('message.common.submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('pagescript')
<script type="application/javascript">
    'use strict';
    window.URL_ROUTE = "{{ route('state.index') }}";

    window.validationMessages = {
    };

    // ── AJAX-driven typeahead (LOOKUP-CONSOLIDATION-001) ──
    var countryInst = null;
    var countryEl = document.querySelector('#country_id');

    var table = '';
    $(function() {
        // Country options pre-loaded server-side; just enable client-side search.
        if (typeof initErpSelect === 'function') {
            countryInst = initErpSelect(countryEl, { placeholder: '{{ __("message.common.select") }}' });
        }

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
                {
                    data: 'country',
                    name: 'country.name',
                    render: function(data) {
                        if (data) return data.name + (data.code ? ' - ' + data.code : '');
                        return '-';
                    }
                },
                { data: 'name', name: 'name' },
                { data: 'code', name: 'code' },
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
        $('#error_country_id, #error_name, #error_code').html('');
        $('#id').val('');
        // Reset country dropdown
        if (countryInst) countryInst.setValue('');
        document.querySelector('#country_id').value = '';
        $('#inlineModal').find('.erp-btn-locked').each(function() {
            $(this).css({ opacity: '', pointerEvents: '' }).removeClass('erp-btn-locked').removeData('erp-original-pointer');
        });
        $("#save").attr('data-route', "{{ route('state.store') }}")
            .removeClass('update').addClass('save')
            .html('<i class="fa-solid fa-check mr-1.5 text-xs"></i> {{ __("message.common.submit") }}')
            .prop('disabled', false)
            .removeAttr('style')
            .removeData('erp-original-html')
            .removeData('erp-original-style');
        $("#modalTitle").html("{{ __('state::message.add') }}");

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
        var url = "{{ route('state.edit', ':id') }}".replace(':id', id);
        $("#save").attr('data-route', "{{ route('state.update', ':id') }}".replace(':id', id));
        $.ajax({
            type: "GET",
            url: url,
            dataType: 'json',
            success: function(response) {
                if (response.status_code == 200) {
                    $("#modalTitle").html("{{ __('state::message.edit') }}");
                    $("#name").val(response.result.name);
                    $("#code").val(response.result.code);
                    $("#id").val(id);
                    // Country options are pre-loaded server-side; selected
                    // value is guaranteed to be in the <select>.
                    var cid = String(response.result.country_id);
                    if (countryInst) countryInst.setValue(cid);
                    countryEl.value = cid;
                    $('#inlineModal').removeClass('hidden');
                } else if (response.status_code == 201 || response.status_code == 404) {
                    erpToast({ title: 'Warning', message: response.message, type: 'warning' });
                } else {
                    erpToast({ title: 'Error', message: response.message, type: 'error' });
                }
            }
        });
    });
</script>
@endsection
