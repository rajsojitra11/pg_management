@extends('layouts.app-tw')
@section('title', __('year::message.list'))
@section('nav-module', 'year')
@section('breadcrumb', 'Home > Masters > Year')

@section('content')
{{-- Page Header --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ __('year::message.list') }}</h1>
    </div>
    <div class="flex items-center gap-2">
        @can('year-create')
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
                    <th>{{ __('year::message.name') }}</th>
                    <th>{{ __('year::message.default') }}</th>
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
                <h3 class="text-lg font-semibold text-zinc-900" id="exampleModalTitle">{{ __('year::message.add') }}</h3>
                <button type="button" class="text-zinc-400 hover:text-zinc-600 erp-inline-modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            {{-- Body --}}
            <div id="body">
                <form id="form" action="javascript:void(0);" method="POST" novalidate>
                    @csrf
                    <div class="p-4 space-y-4">
                        {{-- Name --}}
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1" for="name">
                                {{ __('year::message.name') }}<span class="text-red-500"> *</span>
                            </label>
                            <input type="text" required
                                   class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                   name="name" id="name"
                                   placeholder="{{ __('year::message.name') }}"
                                   value="{{ old('name') }}">
                            <div class="mt-1 text-sm text-red-500" id="error_name"></div>
                        </div>

                        {{-- Default Toggle --}}
                        <div class="flex items-center gap-3">
                            <label class="text-sm font-medium text-zinc-700">{{ __('year::message.default') }}</label>
                            <div class="erp-toggle relative cursor-pointer" style="width:36px;height:20px;background:var(--erp-primary);border-radius:9999px;transition:background-color 0.2s;"
                                 onclick="var cb=this.querySelector('input');cb.checked=!cb.checked;this.style.backgroundColor=cb.checked?'var(--erp-primary)':'var(--erp-border)';this.querySelector('.erp-toggle-dot').style.transform=cb.checked?'translateX(16px)':'translateX(0)';">
                                <input type="checkbox" name="set_default" id="set_default" value="1" checked class="absolute" style="opacity:0;width:0;height:0;">
                                <div class="erp-toggle-dot absolute bg-white rounded-full shadow" style="width:16px;height:16px;top:2px;left:2px;transition:transform 0.2s;transform:translateX(16px);"></div>
                            </div>
                        </div>


                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-end gap-2 p-4 border-t border-zinc-200">
                        <button type="button" class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center erp-inline-modal-close">
                            {{ __('message.common.cancel') }}
                        </button>
                        <button type="button" id="save" data-route="{{ route('year.store') }}"
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


<script type="application/javascript">
    'use strict';
    window.URL_ROUTE = "{{ route('year.index') }}";

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
                { data: 'name', name: 'name' },
                { data: 'set_default', name: 'set_default', width: '100px' },
                { data: 'action', name: 'action', orderable: false, sortable: false, width: '160px' }
            ]
        });
    });

    // Validation handled by validateFormFields() in save.js/update.js
    // Required fields use HTML required attribute

    // ── Modal close/reset ──────────────────────────
    function resetInlineModal() {
        $('#inlineModal').addClass('hidden');
        $('#form')[0].reset();
        $('#form').find('.border-red-500').removeClass('border-red-500');
        $('#form').find('.erp-field-error').remove();
        $('#form').find('.erp-form-error-banner').hide();
        $('#error_name').html('');
        $('#set_default').prop('checked', true);
        var toggle = $('#set_default').closest('.erp-toggle');
        toggle.css('backgroundColor', 'var(--erp-primary)');
        toggle.find('.erp-toggle-dot').css('transform', 'translateX(16px)');
        $('#inlineModal').find('.erp-btn-locked').each(function() {
            $(this).css({ opacity: '', pointerEvents: '' }).removeClass('erp-btn-locked').removeData('erp-original-pointer');
        });
        $("#save").attr('data-route', "{{ route('year.store') }}")
            .removeClass('update').addClass('save')
            .html('<i class="fa-solid fa-check mr-1.5 text-xs"></i> {{ __("message.common.submit") }}')
            .prop('disabled', false)
            .removeAttr('style')
            .removeData('erp-original-html')
            .removeData('erp-original-style');
        $("#exampleModalTitle").html("{{ __('year::message.add') }}");

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
        var url = "{{ route('year.edit', ':id') }}".replace(':id', id);
        $("#save").attr('data-route', "{{ route('year.update', ':id') }}".replace(':id', id));
        $.ajax({
            type: "GET",
            url: url,
            dataType: 'json',
            success: function(response) {
                if (response.status_code == 200) {
                    $("#exampleModalTitle").html("{{ __('year::message.edit') }}");
                    $("#name").val(response.result.name);
                    var isDefault = response.result.set_default == 1;
                    $('#set_default').prop('checked', isDefault);
                    var toggle = $('#set_default').closest('.erp-toggle');
                    toggle.css('backgroundColor', isDefault ? 'var(--erp-primary)' : 'var(--erp-border)');
                    toggle.find('.erp-toggle-dot').css('transform', isDefault ? 'translateX(16px)' : 'translateX(0)');
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
