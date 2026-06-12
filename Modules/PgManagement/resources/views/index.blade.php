@extends('layouts.app-tw')
@section('title', __('pgmanagement::message.pg_management_master'))
@section('nav-module', 'pgmanagement')
@section('breadcrumb', 'Home > PG Management')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ __('pgmanagement::message.list') }}</h1>
    </div>
    <div class="flex items-center gap-2">
        @can('pgmanagement-create')
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
                <input type="text" id="filterSearch" name="filter_search" placeholder="{{ __('pgmanagement::message.search_placeholder') }}" class="flex-1 min-w-0 bg-transparent px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:outline-none">
            </div>
        </div>
        <div class="lg:col-span-4 flex items-center gap-2 justify-end lg:col-start-9">
            <button type="button" class="search h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800">{{ __('pgmanagement::message.apply') }}</button>
            <button type="button" class="reset h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm text-zinc-500 hover:bg-zinc-50">{{ __('pgmanagement::message.reset') }}</button>
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
                    <th>{{ __('pgmanagement::message.pg_name') }}</th>
                    <th>{{ __('pgmanagement::message.owner') }}</th>
                    <th>{{ __('pgmanagement::message.mobile_no') }}</th>
                    <th>{{ __('pgmanagement::message.total_block') }}</th>
                    <th>{{ __('pgmanagement::message.total_room') }}</th>
                    <th>{{ __('pgmanagement::message.pincode') }}</th>
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
                <h3 class="text-lg font-semibold text-zinc-900" id="exampleModalTitle">{{ __('pgmanagement::message.add') }}</h3>
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
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="pg_name">
                                    {{ __('pgmanagement::message.pg_name') }}<span class="text-red-500"> *</span>
                                </label>
                                <input type="text" required
                                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                       name="pg_name" id="pg_name"
                                       placeholder="{{ __('pgmanagement::message.enter_pg_name') }}">
                                <div class="mt-1 text-sm text-red-500" id="error_pg_name"></div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="owner_id">
                                    {{ __('pgmanagement::message.owner') }}
                                </label>
                                <select name="owner_id" id="owner_id" style="width:100%;">
                                    <option value="">{{ __('message.common.select') }}</option>
                                    @foreach ($pgAdminUsers as $user)
                                    <option value="{{ $user->id }}">{{ $user->email }}</option>
                                    @endforeach
                                </select>
                                <div class="mt-1 text-sm text-red-500" id="error_owner_id"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="mobile_no">
                                    {{ __('pgmanagement::message.mobile_no') }}
                                </label>
                                <input type="text"
                                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                       name="mobile_no" id="mobile_no"
                                       placeholder="{{ __('pgmanagement::message.enter_mobile_no') }}">
                                <div class="mt-1 text-sm text-red-500" id="error_mobile_no"></div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="total_block">
                                    {{ __('pgmanagement::message.total_block') }}
                                </label>
                                <input type="number" min="0"
                                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                       name="total_block" id="total_block"
                                       placeholder="{{ __('pgmanagement::message.enter_total_block') }}">
                                <div class="mt-1 text-sm text-red-500" id="error_total_block"></div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="total_room">
                                    {{ __('pgmanagement::message.total_room') }}
                                </label>
                                <input type="number" min="0"
                                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                       name="total_room" id="total_room"
                                       placeholder="{{ __('pgmanagement::message.enter_total_room') }}">
                                <div class="mt-1 text-sm text-red-500" id="error_total_room"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="country_id">
                                    {{ __('pgmanagement::message.country') }}
                                </label>
                                <select name="country_id" id="country_id"
                                        data-fresh-prefetch="{{ route('lookup.countries') }}"
                                        data-placeholder="— Select —"
                                        class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm">
                                    <option value=""></option>
                                </select>
                                <div class="mt-1 text-sm text-red-500" id="error_country_id"></div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="state_id">
                                    {{ __('pgmanagement::message.state') }}
                                </label>
                                <select name="state_id" id="state_id"
                                        data-placeholder="— Select —"
                                        class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm">
                                    <option value=""></option>
                                </select>
                                <div class="mt-1 text-sm text-red-500" id="error_state_id"></div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="city_id">
                                    {{ __('pgmanagement::message.city') }}
                                </label>
                                <select name="city_id" id="city_id"
                                        data-placeholder="— Select —"
                                        class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm">
                                    <option value=""></option>
                                </select>
                                <div class="mt-1 text-sm text-red-500" id="error_city_id"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="pincode">
                                    {{ __('pgmanagement::message.pincode') }}
                                </label>
                                <input type="text"
                                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                       name="pincode" id="pincode"
                                       placeholder="{{ __('pgmanagement::message.enter_pincode') }}">
                                <div class="mt-1 text-sm text-red-500" id="error_pincode"></div>
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

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1" for="address">
                                {{ __('pgmanagement::message.address') }}
                            </label>
                            <textarea
                                   class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                   name="address" id="address" rows="3"
                                   placeholder="{{ __('pgmanagement::message.enter_address') }}"></textarea>
                            <div class="mt-1 text-sm text-red-500" id="error_address"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 p-4 border-t border-zinc-200">
                        <button type="button" class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center erp-inline-modal-close">
                            {{ __('message.common.cancel') }}
                        </button>
                        <button type="button" id="save" data-route="{{ route('pgmanagement.store') }}"
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
        <div class="relative w-full max-w-2xl rounded-lg border border-zinc-200 bg-white shadow-xl">
            <div class="flex items-center justify-between p-4 border-b border-zinc-200">
                <h3 class="text-lg font-semibold text-zinc-900" id="viewModalTitle">{{ __('pgmanagement::message.pg_management') }}</h3>
                <button type="button" class="text-zinc-400 hover:text-zinc-600 erp-inline-modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-5 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('pgmanagement::message.pg_name') }}</p>
                        <p class="text-sm font-semibold text-zinc-900" id="view_pg_name">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('pgmanagement::message.owner') }}</p>
                        <p class="text-sm text-zinc-900" id="view_owner">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('pgmanagement::message.mobile_no') }}</p>
                        <p class="text-sm text-zinc-900" id="view_mobile_no">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('pgmanagement::message.total_block') }}</p>
                        <p class="text-sm text-zinc-900" id="view_total_block">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('pgmanagement::message.total_room') }}</p>
                        <p class="text-sm text-zinc-900" id="view_total_room">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('pgmanagement::message.country') }}</p>
                        <p class="text-sm text-zinc-900" id="view_country_id">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('pgmanagement::message.state') }}</p>
                        <p class="text-sm text-zinc-900" id="view_state_id">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('pgmanagement::message.city') }}</p>
                        <p class="text-sm text-zinc-900" id="view_city_id">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('pgmanagement::message.pincode') }}</p>
                        <p class="text-sm text-zinc-900" id="view_pincode">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('message.common.status') }}</p>
                        <p class="text-sm text-zinc-900" id="view_status">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('pgmanagement::message.address') }}</p>
                        <p class="text-sm text-zinc-900" id="view_address">-</p>
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
    window.URL_ROUTE = "{{ route('pgmanagement.index') }}";

    window.validationMessages = {};

    var table = '';
    var ownerSelectInst = null;

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
                { data: 'pg_name', name: 'pg_name' },
                { data: 'owner', name: 'owner', orderable: false, searchable: false },
                { data: 'mobile_no', name: 'mobile_no' },
                { data: 'total_block', name: 'total_block' },
                { data: 'total_room', name: 'total_room' },
                { data: 'pincode', name: 'pincode' },
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
            ownerSelectInst = initErpSelect('#owner_id', { allowClear: true, placeholder: '{{ __("message.common.select") }}' });
        }

        // Country → State → City cascade
        (function() {
            var $country = $('#country_id');
            var $state = $('#state_id');
            var $city = $('#city_id');
            if (!$country.length || !$state.length || !$city.length) return;

            var stateInst = null;
            var cityInst = null;

            var check = setInterval(function() {
                if ($country.next('.erp-select-wrapper').length) {
                    clearInterval(check);
                    $country.on('change', function() {
                        var val = $(this).val();
                        if (!val) {
                            if (stateInst) { stateInst.setOptions([]); stateInst.setValue(''); }
                            if (cityInst) { cityInst.setOptions([]); cityInst.setValue(''); }
                            return;
                        }
                        $.get('{{ route("lookup.states") }}', { country_id: val, limit: 9999 }, function(data) {
                            if (!stateInst) {
                                stateInst = erpSearchSelect('#state_id', { placeholder: '— Select —', allowClear: true });
                            }
                            stateInst.setOptions(data || []);
                            stateInst.setValue('');
                            if (cityInst) { cityInst.setOptions([]); cityInst.setValue(''); }
                        });
                    });
                }
            }, 100);

            var check2 = setInterval(function() {
                if ($state.next('.erp-select-wrapper').length) {
                    clearInterval(check2);
                    $state.on('change', function() {
                        var val = $(this).val();
                        if (!val) {
                            if (cityInst) { cityInst.setOptions([]); cityInst.setValue(''); }
                            return;
                        }
                        $.get('{{ route("lookup.cities") }}', { state_id: val, limit: 9999 }, function(data) {
                            if (!cityInst) {
                                cityInst = erpSearchSelect('#city_id', { placeholder: '— Select —', allowClear: true });
                            }
                            cityInst.setOptions(data || []);
                            cityInst.setValue('');
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
        $('#error_pg_name').html('');
        $('#error_owner_id').html('');
        $('#error_mobile_no').html('');
        $('#error_total_block').html('');
        $('#error_total_room').html('');
        $('#error_country_id').html('');
        $('#error_state_id').html('');
        $('#error_city_id').html('');
        $('#error_pincode').html('');
        $('#error_address').html('');
        $('#error_status').html('');
        $('#inlineModal').find('.erp-btn-locked').each(function() {
            $(this).css({ opacity: '', pointerEvents: '' }).removeClass('erp-btn-locked').removeData('erp-original-pointer');
        });
        $("#save").attr('data-route', "{{ route('pgmanagement.store') }}")
            .removeClass('update').addClass('save')
            .html('<i class="fa-solid fa-check mr-1.5 text-xs"></i> {{ __("message.common.submit") }}')
            .prop('disabled', false)
            .removeAttr('style')
            .removeData('erp-original-html')
            .removeData('erp-original-style');
        $("#exampleModalTitle").html("{{ __('pgmanagement::message.add') }}");

        if (ownerSelectInst && typeof ownerSelectInst.setValue === 'function') {
            ownerSelectInst.setValue('');
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
        var url = "{{ route('pgmanagement.show', ':id') }}".replace(':id', id);
        $.ajax({
            type: "GET",
            url: url,
            dataType: 'json',
            success: function(response) {
                if (response.status_code == 200) {
                    var d = response.result;
                    $('#view_pg_name').text(d.pg_name || '-');
                    $('#view_owner').text(d.owner ? d.owner.email || d.owner_id : '-');
                    $('#view_mobile_no').text(d.mobile_no || '-');
                    $('#view_total_block').text(d.total_block || '-');
                    $('#view_total_room').text(d.total_room || '-');
                    $('#view_country_id').text(d.country_id || '-');
                    $('#view_state_id').text(d.state_id || '-');
                    $('#view_city_id').text(d.city_id || '-');
                    $('#view_pincode').text(d.pincode || '-');
                    $('#view_address').text(d.address || '-');
                    $('#view_status').text(d.status ? d.status.charAt(0).toUpperCase() + d.status.slice(1) : '-');
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
        var url = "{{ route('pgmanagement.edit', ':id') }}".replace(':id', id);
        $("#save").attr('data-route', "{{ route('pgmanagement.update', ':id') }}".replace(':id', id));
        $.ajax({
            type: "GET",
            url: url,
            dataType: 'json',
            success: function(response) {
                if (response.status_code == 200) {
                    $("#exampleModalTitle").html("{{ __('pgmanagement::message.edit') }}");
                    $("#pg_name").val(response.result.pg_name);
                    $("#mobile_no").val(response.result.mobile_no);
                    $("#total_block").val(response.result.total_block);
                    $("#total_room").val(response.result.total_room);
                    $("#country_id").val(response.result.country_id);
                    $("#state_id").val(response.result.state_id);
                    $("#city_id").val(response.result.city_id);
                    $("#pincode").val(response.result.pincode);
                    $("#address").val(response.result.address);
                    $("#status").val(response.result.status);
                    $("#id").val(id);

                    if (ownerSelectInst && typeof ownerSelectInst.setValue === 'function') {
                        ownerSelectInst.setValue(response.result.owner_id || '');
                    } else {
                        $("#owner_id").val(response.result.owner_id);
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
