@extends('layouts.app-tw')
@section('title', __('service::message.module_name'))
@section('nav-module', 'service')
@section('breadcrumb', 'Home > Service > Manage Service')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ __('service::message.service_list') }}</h1>
    </div>
    <div class="flex items-center gap-2">
        @can('service-create')
        <button type="button" class="h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 whitespace-nowrap inline-flex items-center new-create" onclick="resetInlineModal();$('#inlineModal').removeClass('hidden')">
            <i class="fa-solid fa-plus mr-1.5 text-xs"></i> {{ __('message.common.addNew') }}
        </button>
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
                <input type="text" id="filterSearch" name="filter_search" placeholder="{{ __('service::message.service_search_placeholder') }}" class="flex-1 min-w-0 bg-transparent px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:outline-none">
            </div>
        </div>
        <div class="lg:col-span-4 flex items-center gap-2 justify-end lg:col-start-9">
            <button type="button" class="search h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800">{{ __('service::message.apply') }}</button>
            <button type="button" class="reset h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm text-zinc-500 hover:bg-zinc-50">{{ __('service::message.reset') }}</button>
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
                    <th>{{ __('service::message.service_name') }}</th>
                    <th>{{ __('service::message.category') }}</th>
                    <th>{{ __('message.common.status') }}</th>
                    <th>{{ __('message.common.action') }}</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- Add/Edit Modal --}}
<div id="inlineModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 erp-inline-modal-close"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="relative w-full max-w-2xl rounded-lg border border-zinc-200 bg-white shadow-xl">
            <div class="flex items-center justify-between p-4 border-b border-zinc-200">
                <h3 class="text-lg font-semibold text-zinc-900" id="exampleModalTitle">{{ __('service::message.add_service') }}</h3>
                <button type="button" class="text-zinc-400 hover:text-zinc-600 erp-inline-modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div id="body">
                <form id="form" action="javascript:void(0);" method="POST" novalidate>
                    @csrf
                    <div class="p-4 space-y-4">
                        <input type="hidden" name="id" id="id" value="">

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1" for="service_category_id">
                                {{ __('service::message.category') }}<span class="text-red-500"> *</span>
                            </label>
                            <select name="service_category_id" id="service_category_id" style="width:100%;">
                                <option value="">{{ __('message.common.select') }}</option>
                                @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->service_category_name }}</option>
                                @endforeach
                            </select>
                            <div class="mt-1 text-sm text-red-500" id="error_service_category_id"></div>
                        </div>

                        {{-- Multi-service rows (shown only in create mode) --}}
                        <div id="multiServiceSection" class="hidden">
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-zinc-700">
                                    {{ __('service::message.services') }}<span class="text-red-500"> *</span>
                                </label>
                                <button type="button" id="addServiceRow" class="h-7 px-3 rounded-md bg-emerald-500 text-white text-xs font-medium hover:bg-emerald-600 inline-flex items-center">
                                    <i class="fa-solid fa-plus mr-1 text-[10px]"></i> {{ __('service::message.add_row') }}
                                </button>
                            </div>
                            <div class="overflow-x-auto border border-zinc-200 rounded-lg">
                                <table class="w-full text-sm" id="servicesTable">
                                    <thead>
                                        <tr class="bg-zinc-50 border-b border-zinc-200">
                                            <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 w-1/2">{{ __('service::message.service_name') }} <span class="text-red-500">*</span></th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 w-1/4">{{ __('message.common.status') }}</th>
                                            <th class="px-3 py-2 text-center text-xs font-medium text-zinc-500 w-20">{{ __('message.common.action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="servicesTableBody">
                                        {{-- Rows added dynamically via JS --}}
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-1 text-sm text-red-500" id="error_services"></div>
                        </div>

                        {{-- Single service fields (shown in edit mode) --}}
                        <div id="singleServiceSection" class="hidden">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="service_name">
                                    {{ __('service::message.service_name') }}<span class="text-red-500"> *</span>
                                </label>
                                <input type="text" required
                                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                       name="service_name" id="service_name"
                                       placeholder="{{ __('service::message.enter_service_name') }}">
                                <div class="mt-1 text-sm text-red-500" id="error_service_name"></div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="status">
                                    {{ __('message.common.status') }}
                                </label>
                                <select name="status" id="status"
                                        class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500">
                                    <option value="active">{{ __('message.common.active') }}</option>
                                    <option value="inactive">{{ __('message.common.inactive') }}</option>
                                </select>
                                <div class="mt-1 text-sm text-red-500" id="error_status"></div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 p-4 border-t border-zinc-200">
                        <button type="button" class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center erp-inline-modal-close">
                            {{ __('message.common.cancel') }}
                        </button>
                        <button type="button" id="save" data-route="{{ route('service.store') }}"
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
<script type="application/javascript">
    'use strict';
    window.URL_ROUTE = "{{ route('service.index') }}";

    var table = '';
    var categorySelectInst = null;

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
                { data: 'service_name', name: 'service_name' },
                { data: 'category_name', name: 'category_name', orderable: false, searchable: false },
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

        if (typeof initErpSelect === 'function') {
            categorySelectInst = initErpSelect('#service_category_id', { allowClear: true, placeholder: '{{ __("message.common.select") }}' });
        }

        // Add service row
        $(document).on('click', '#addServiceRow', function() {
            addServiceRow();
        });

        // Remove service row
        $(document).on('click', '.remove-service-row', function() {
            $(this).closest('tr').remove();
        });
    });

    function addServiceRow(serviceName, status) {
        var rowId = 'row_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
        var html = '<tr class="border-b border-zinc-100 hover:bg-zinc-50" data-row-id="' + rowId + '">' +
            '<td class="px-3 py-2">' +
            '<input type="text" name="services[' + rowId + '][service_name]" value="' + (serviceName || '') + '" required ' +
            'class="w-full rounded-md border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500" ' +
            'placeholder="{{ __("service::message.enter_service_name") }}">' +
            '<div class="mt-0.5 text-xs text-red-500 error-service_name"></div>' +
            '</td>' +
            '<td class="px-3 py-2">' +
            '<select name="services[' + rowId + '][status]" ' +
            'class="w-full rounded-md border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-700 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500">' +
            '<option value="active" ' + (status === 'active' || !status ? 'selected' : '') + '>{{ __("message.common.active") }}</option>' +
            '<option value="inactive" ' + (status === 'inactive' ? 'selected' : '') + '>{{ __("message.common.inactive") }}</option>' +
            '</select>' +
            '</td>' +
            '<td class="px-3 py-2 text-center">' +
            '<button type="button" class="remove-service-row p-1.5 rounded-md text-red-400 hover:text-red-600 hover:bg-red-50 inline-flex items-center" title="{{ __("message.common.delete") }}">' +
            '<i class="fa-solid fa-trash text-xs"></i>' +
            '</button>' +
            '</td>' +
            '</tr>';
        $('#servicesTableBody').append(html);
    }

    function resetInlineModal() {
        $('#inlineModal').addClass('hidden');
        $('#form')[0].reset();
        $('#form').find('.border-red-500').removeClass('border-red-500');
        $('#form').find('.erp-field-error').remove();
        $('#form').find('.erp-form-error-banner').hide();
        $('#error_service_category_id').html('');
        $('#error_service_name').html('');
        $('#error_services').html('');
        $('#error_status').html('');
        $('#servicesTableBody').empty();
        $('#multiServiceSection').addClass('hidden');
        $('#singleServiceSection').addClass('hidden');
        $('#inlineModal').find('.erp-btn-locked').each(function() {
            $(this).css({ opacity: '', pointerEvents: '' }).removeClass('erp-btn-locked').removeData('erp-original-pointer');
        });
        $("#save").attr('data-route', "{{ route('service.store') }}")
            .removeClass('update').addClass('save')
            .html('<i class="fa-solid fa-check mr-1.5 text-xs"></i> {{ __("message.common.submit") }}')
            .prop('disabled', false)
            .removeAttr('style')
            .removeData('erp-original-html')
            .removeData('erp-original-style');
        $("#exampleModalTitle").html("{{ __('service::message.add_service') }}");

        if (categorySelectInst && typeof categorySelectInst.setValue === 'function') {
            categorySelectInst.setValue('');
        }
    }

    $(document).on('click', '.erp-inline-modal-close', function(e) {
        e.preventDefault();
        resetInlineModal();
    });

    // On category change in create mode, show multi-service section
    $(document).on('change', '#service_category_id', function() {
        if ($("#save").hasClass('save') && $(this).val()) {
            $('#multiServiceSection').removeClass('hidden');
        } else if ($("#save").hasClass('save')) {
            $('#multiServiceSection').addClass('hidden');
        }
    });

    // Handle form submission
    $(document).on('click', '#save.save', function(e) {
        e.preventDefault();
        var btn = $(this);
        var form = $('#form');
        var formData = new FormData(form[0]);
        var route = btn.data('route');

        // If in create mode with multi-service section visible
        if ($('#multiServiceSection').is(':visible') && !$('#multiServiceSection').hasClass('hidden')) {
            var categoryId = $('#service_category_id').val();
            if (!categoryId) {
                $('#error_service_category_id').html('{{ __("service::message.select_category") }}');
                return;
            }
            $('#error_service_category_id').html('');

            var rows = $('#servicesTableBody tr');
            var hasError = false;
            rows.each(function() {
                var input = $(this).find('input[name$="[service_name]"]');
                if (!input.val().trim()) {
                    input.addClass('border-red-500');
                    $(this).find('.error-service_name').html('{{ __("service::message.enter_service_name") }}');
                    hasError = true;
                } else {
                    input.removeClass('border-red-500');
                    $(this).find('.error-service_name').html('');
                }
            });

            if (hasError || rows.length === 0) {
                if (rows.length === 0) {
                    $('#error_services').html('{{ __("service::message.add_at_least_one_service") }}');
                }
                return;
            }
            $('#error_services').html('');
        }

        // Collect services array data
        var services = [];
        $('#servicesTableBody tr').each(function() {
            var serviceName = $(this).find('input[name$="[service_name]"]').val();
            var status = $(this).find('select[name$="[status]"]').val();
            if (serviceName && serviceName.trim()) {
                services.push({
                    service_name: serviceName.trim(),
                    status: status || 'active'
                });
            }
        });

        if (services.length > 0) {
            formData.append('services', JSON.stringify(services));
        }

        $.ajax({
            type: "POST",
            url: route,
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status_code == 200) {
                    toastr.success(response.message, "Success");
                    resetInlineModal();
                    table.ajax.reload();
                } else {
                    toastr.error(response.message, "Error");
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, val) {
                        var $el = $('#error_' + key.replace(/\.\d+/g, ''));
                        if ($el.length) {
                            $el.html(val[0]);
                        }
                    });
                } else {
                    toastr.error('Something went wrong. Please try again.', "Error");
                }
            }
        });
    });

    // Handle update submission
    $(document).on('click', '#save.update', function(e) {
        e.preventDefault();
        var btn = $(this);
        var form = $('#form');
        var formData = new FormData(form[0]);
        formData.append('_method', 'PUT');
        var route = btn.data('route');

        $.ajax({
            type: "POST",
            url: route,
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status_code == 200) {
                    toastr.success(response.message, "Success");
                    resetInlineModal();
                    table.ajax.reload();
                } else {
                    toastr.error(response.message, "Error");
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, val) {
                        var $el = $('#error_' + key);
                        if ($el.length) {
                            $el.html(val[0]);
                        }
                    });
                } else {
                    toastr.error('Something went wrong. Please try again.', "Error");
                }
            }
        });
    });

    $(document).on('click', '.edit', function(e) {
        e.preventDefault();
        resetInlineModal();
        $("#save").attr('data-route', '').removeClass('save').addClass('update');
        var id = $(this).attr('data-id');
        var url = "{{ route('service.edit', ':id') }}".replace(':id', id);
        $("#save").attr('data-route', "{{ route('service.update', ':id') }}".replace(':id', id));
        $.ajax({
            type: "GET",
            url: url,
            dataType: 'json',
            success: function(response) {
                if (response.status_code == 200) {
                    $("#exampleModalTitle").html("{{ __('service::message.edit_service') }}");
                    $("#service_name").val(response.result.service_name);
                    $("#status").val(response.result.status);
                    $("#id").val(id);

                    // Show single service section for edit
                    $('#singleServiceSection').removeClass('hidden');
                    $('#multiServiceSection').addClass('hidden');

                    if (categorySelectInst && typeof categorySelectInst.setValue === 'function') {
                        categorySelectInst.setValue(response.result.service_category_id || '');
                    } else {
                        $("#service_category_id").val(response.result.service_category_id);
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
