@extends('layouts.app-tw')
@section('title', __('room::message.room_master'))
@section('nav-module', 'room')
@section('breadcrumb', 'Home > Room > Manage Room')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ __('room::message.room_list') }}</h1>
    </div>
    <div class="flex items-center gap-2">
        @can('room-create')
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
                <input type="text" id="filterSearch" name="filter_search" placeholder="{{ __('room::message.room_search_placeholder') }}" class="flex-1 min-w-0 bg-transparent px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:outline-none">
            </div>
        </div>
        <div class="lg:col-span-4 flex items-center gap-2 justify-end lg:col-start-9">
            <button type="button" class="search h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800">{{ __('room::message.apply') }}</button>
            <button type="button" class="reset h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm text-zinc-500 hover:bg-zinc-50">{{ __('room::message.reset') }}</button>
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
                    <th>{{ __('room::message.room_no') }}</th>
                    <th>{{ __('room::message.pg') }}</th>
                    <th>{{ __('room::message.category') }}</th>
                    <th>{{ __('room::message.bed_capacity') }}</th>
                    <th>{{ __('room::message.rent_amount') }}</th>
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
        <div class="relative w-full max-w-lg rounded-lg border border-zinc-200 bg-white shadow-xl">
            <div class="flex items-center justify-between p-4 border-b border-zinc-200">
                <h3 class="text-lg font-semibold text-zinc-900" id="exampleModalTitle">{{ __('room::message.add_room') }}</h3>
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
                            <label class="block text-sm font-medium text-zinc-700 mb-1" for="pg_id">
                                {{ __('room::message.pg') }}<span class="text-red-500"> *</span>
                            </label>
                            <select name="pg_id" id="pg_id" style="width:100%;">
                                <option value="">{{ __('message.common.select') }}</option>
                                @foreach ($pgList as $pg)
                                <option value="{{ $pg->id }}">{{ $pg->pg_name }}</option>
                                @endforeach
                            </select>
                            <div class="mt-1 text-sm text-red-500" id="error_pg_id"></div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1" for="category_id">
                                {{ __('room::message.category') }}<span class="text-red-500"> *</span>
                            </label>
                            <select name="category_id" id="category_id"
                                    data-placeholder="— Select —"
                                    class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm">
                                <option value=""></option>
                            </select>
                            <div class="mt-1 text-sm text-red-500" id="error_category_id"></div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="room_no">
                                    {{ __('room::message.room_no') }}<span class="text-red-500"> *</span>
                                </label>
                                <input type="text" required
                                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                       name="room_no" id="room_no"
                                       placeholder="{{ __('room::message.enter_room_no') }}">
                                <div class="mt-1 text-sm text-red-500" id="error_room_no"></div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="bed_capacity">
                                    {{ __('room::message.bed_capacity') }}
                                </label>
                                <input type="number" min="0"
                                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                       name="bed_capacity" id="bed_capacity"
                                       placeholder="{{ __('room::message.enter_bed_capacity') }}">
                                <div class="mt-1 text-sm text-red-500" id="error_bed_capacity"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="rent_amount">
                                    {{ __('room::message.rent_amount') }}
                                </label>
                                <input type="number" step="0.01" min="0"
                                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                       name="rent_amount" id="rent_amount"
                                       placeholder="{{ __('room::message.enter_rent_amount') }}">
                                <div class="mt-1 text-sm text-red-500" id="error_rent_amount"></div>
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
                        <button type="button" id="save" data-route="{{ route('room.store') }}"
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

{{-- View Modal --}}
<div id="viewModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 erp-inline-modal-close"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="relative w-full max-w-lg rounded-lg border border-zinc-200 bg-white shadow-xl">
            <div class="flex items-center justify-between p-4 border-b border-zinc-200">
                <h3 class="text-lg font-semibold text-zinc-900" id="viewModalTitle">{{ __('room::message.view_room') }}</h3>
                <button type="button" class="text-zinc-400 hover:text-zinc-600 erp-inline-modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-6">
                <p class="text-sm font-medium text-zinc-500 mb-4 text-center">{{ __('room::message.bed_capacity') }}</p>
                <div class="flex flex-wrap justify-center gap-4" id="view_bed_capacity">-</div>
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
    window.URL_ROUTE = "{{ route('room.index') }}";

    window.validationMessages = {};

    var table = '';
    var pgSelectInst = null;
    var categoryInst = null;

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
                { data: 'room_no', name: 'room_no' },
                { data: 'pg_name', name: 'pg_name', orderable: false, searchable: false },
                { data: 'category_name', name: 'category_name', orderable: false, searchable: false },
                { data: 'bed_capacity', name: 'bed_capacity' },
                { data: 'rent_amount', name: 'rent_amount' },
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
            pgSelectInst = initErpSelect('#pg_id', { allowClear: true, placeholder: '{{ __("message.common.select") }}' });
        }

        // PG → Category cascade
        (function() {
            var $pg = $('#pg_id');
            var $category = $('#category_id');
            if (!$pg.length || !$category.length) return;

            var check = setInterval(function() {
                if ($pg.next('.erp-select-wrapper').length) {
                    clearInterval(check);
                    $pg.on('change', function() {
                        var val = $(this).val();
                        if (!val) {
                            if (categoryInst) { categoryInst.setOptions([]); categoryInst.setValue(''); }
                            return;
                        }
                        $.get('{{ route("room.categories-by-pg") }}', { pg_id: val, limit: 9999 }, function(data) {
                            if (!categoryInst) {
                                categoryInst = erpSearchSelect('#category_id', { placeholder: '— Select —', allowClear: true });
                            }
                            categoryInst.setOptions(data || []);
                            categoryInst.setValue('');
                        });
                    });
                }
            }, 100);
        })();
    });

    function resetInlineModal() {
        $('#inlineModal').addClass('hidden');
        $('#form')[0].reset();
        $('#form').find('.border-red-500').removeClass('border-red-500');
        $('#form').find('.erp-field-error').remove();
        $('#form').find('.erp-form-error-banner').hide();
        $('#error_pg_id').html('');
        $('#error_category_id').html('');
        $('#error_room_no').html('');
        $('#error_bed_capacity').html('');
        $('#error_rent_amount').html('');
        $('#error_status').html('');
        $('#inlineModal').find('.erp-btn-locked').each(function() {
            $(this).css({ opacity: '', pointerEvents: '' }).removeClass('erp-btn-locked').removeData('erp-original-pointer');
        });
        $("#save").attr('data-route', "{{ route('room.store') }}")
            .removeClass('update').addClass('save')
            .html('<i class="fa-solid fa-check mr-1.5 text-xs"></i> {{ __("message.common.submit") }}')
            .prop('disabled', false)
            .removeAttr('style')
            .removeData('erp-original-html')
            .removeData('erp-original-style');
        $("#exampleModalTitle").html("{{ __('room::message.add_room') }}");

        if (pgSelectInst && typeof pgSelectInst.setValue === 'function') {
            pgSelectInst.setValue('');
        }
        if (categoryInst && typeof categoryInst.setOptions === 'function') {
            categoryInst.setOptions([]);
            categoryInst.setValue('');
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
        var url = "{{ route('room.show', ':id') }}".replace(':id', id);
        $.ajax({
            type: "GET",
            url: url,
            dataType: 'json',
            success: function(response) {
                if (response.status_code == 200) {
                    var d = response.result;
                    var bedHtml = '';
                    if (d.bed_capacity) {
                        for (var i = 0; i < d.bed_capacity; i++) {
                            bedHtml += '<button type="button" class="bed-select p-3 rounded-lg border-2 border-zinc-200 text-zinc-400 hover:border-zinc-400 hover:text-zinc-600 transition-colors"><i class="fa-solid fa-bed text-3xl"></i></button>';
                        }
                    } else {
                        bedHtml = '-';
                    }
                    $('#view_bed_capacity').html(bedHtml);
                    $('#viewModal').removeClass('hidden');
                } else if (response.status_code == 201 || response.status_code == 404) {
                    toastr.warning(response.message, "Warning");
                } else {
                    toastr.error(response.message, "Error");
                }
            }
        });
    });

    $(document).on('click', '.bed-select', function() {
        $(this).toggleClass('border-zinc-900 bg-zinc-100 text-zinc-900');
    });

    $(document).on('click', '.edit', function(e) {
        e.preventDefault();
        resetInlineModal();
        $("#save").attr('data-route', '').removeClass('save').addClass('update');
        var id = $(this).attr('data-id');
        var url = "{{ route('room.edit', ':id') }}".replace(':id', id);
        $("#save").attr('data-route', "{{ route('room.update', ':id') }}".replace(':id', id));
        $.ajax({
            type: "GET",
            url: url,
            dataType: 'json',
            success: function(response) {
                if (response.status_code == 200) {
                    $("#exampleModalTitle").html("{{ __('room::message.edit_room') }}");
                    $("#room_no").val(response.result.room_no);
                    $("#bed_capacity").val(response.result.bed_capacity);
                    $("#rent_amount").val(response.result.rent_amount);
                    $("#status").val(response.result.status);
                    $("#id").val(id);

                    if (pgSelectInst && typeof pgSelectInst.setValue === 'function') {
                        pgSelectInst.setValue(response.result.pg_id || '');
                    } else {
                        $("#pg_id").val(response.result.pg_id);
                    }

                    // Load categories for the selected PG and set value
                    if (response.result.pg_id) {
                        $.get('{{ route("room.categories-by-pg") }}', { pg_id: response.result.pg_id, limit: 9999 }, function(data) {
                            if (!categoryInst) {
                                categoryInst = erpSearchSelect('#category_id', { placeholder: '— Select —', allowClear: true });
                            }
                            categoryInst.setOptions(data || []);
                            categoryInst.setValue(response.result.category_id || '');
                        });
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
