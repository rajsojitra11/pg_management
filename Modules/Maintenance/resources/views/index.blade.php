@extends('layouts.app-tw')
@section('title', __('maintenance::message.module_name'))
@section('nav-module', 'maintenance')
@section('breadcrumb', 'Home > Maintenance > Manage Maintenance')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ __('maintenance::message.title') }}</h1>
    </div>
    <div class="flex items-center gap-2">
        @can('maintenance-create')
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
                <input type="text" id="filterSearch" name="filter_search" placeholder="{{ __('message.common.search') }}" class="flex-1 min-w-0 bg-transparent px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:outline-none">
            </div>
        </div>
        <div class="lg:col-span-4 flex items-center gap-2 justify-end lg:col-start-9">
            <button type="button" class="search h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800">{{ __('message.common.search') }}</button>
            <button type="button" class="reset h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm text-zinc-500 hover:bg-zinc-50">{{ __('message.common.reset') }}</button>
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
                    <th>{{ __('maintenance::message.no') }}</th>
                    <th>{{ __('maintenance::message.complaint_no') }}</th>
                    <th>{{ __('complaint::message.pg') }}</th>
                    <th>{{ __('complaint::message.room') }}</th>
                    <th>{{ __('maintenance::message.cost') }}</th>
                    <th>{{ __('maintenance::message.maintenance_date') }}</th>
                    <th>{{ __('maintenance::message.proof') }}</th>
                    <th>{{ __('message.common.created_by') }}</th>
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
                <h3 class="text-lg font-semibold text-zinc-900" id="exampleModalTitle">{{ __('maintenance::message.detail') }}</h3>
                <button type="button" class="text-zinc-400 hover:text-zinc-600 erp-inline-modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div id="body">
                <form id="form" action="javascript:void(0);" method="POST" enctype="multipart/form-data" novalidate>
                    @csrf
                    <div class="p-4 space-y-4">
                        <input type="hidden" name="id" id="id" value="">

                        <div class="flex items-center gap-2 p-3 rounded-md bg-zinc-50 border border-zinc-200">
                            <span class="text-sm font-medium text-zinc-500">{{ __('maintenance::message.no') }}:</span>
                            <span class="text-sm font-semibold text-zinc-900" id="display_maintenance_no">—</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="pg_id">
                                    {{ __('complaint::message.pg') }}<span class="text-red-500"> *</span>
                                </label>
                                <select name="pg_id" id="pg_id" required style="width:100%;">
                                    <option value="">{{ __('message.common.select') }}</option>
                                    @foreach ($pgList as $pg)
                                    <option value="{{ $pg->id }}">{{ $pg->pg_name }}</option>
                                    @endforeach
                                </select>
                                <div class="mt-1 text-sm text-red-500" id="error_pg_id"></div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="complaint_id">
                                    {{ __('maintenance::message.complaint_no') }}<span class="text-red-500"> *</span>
                                </label>
                                <select name="complaint_id" id="complaint_id" required
                                        data-placeholder="— Select —"
                                        class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm">
                                    <option value=""></option>
                                </select>
                                <div class="mt-1 text-sm text-red-500" id="error_complaint_id"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="cost">
                                    {{ __('maintenance::message.cost') }}<span class="text-red-500"> *</span>
                                </label>
                                <input type="number" step="0.01" min="0" required
                                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                       name="cost" id="cost"
                                       placeholder="{{ __('maintenance::message.placeholder.enter_cost') }}">
                                <div class="mt-1 text-sm text-red-500" id="error_cost"></div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="maintenance_date">
                                    {{ __('maintenance::message.maintenance_date') }}<span class="text-red-500"> *</span>
                                </label>
                                <input type="date" required
                                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                       name="maintenance_date" id="maintenance_date">
                                <div class="mt-1 text-sm text-red-500" id="error_maintenance_date"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="status">
                                    {{ __('maintenance::message.status') }}<span class="text-red-500"> *</span>
                                </label>
                                <select name="status" id="status" required style="width:100%;">
                                    <option value="">{{ __('maintenance::message.placeholder.select_status') }}</option>
                                    @foreach (['pending', 'in_progress', 'completed', 'cancelled'] as $st)
                                    <option value="{{ $st }}">{{ __("maintenance::message.status_options.{$st}") }}</option>
                                    @endforeach
                                </select>
                                <div class="mt-1 text-sm text-red-500" id="error_status"></div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="proof">
                                    {{ __('maintenance::message.proof') }}
                                </label>
                                <input type="file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200"
                                       name="proof" id="proof">
                                <p class="mt-1 text-xs text-zinc-400">JPG, PNG, PDF, DOC, DOCX (max 5MB)</p>
                                <div class="mt-1 text-sm text-red-500" id="error_proof"></div>
                                <div id="existing_proof" class="mt-1 hidden">
                                    <a href="" target="_blank" class="text-sm text-zinc-900 underline hover:text-zinc-600">
                                        <i class="fa-solid fa-file mr-1"></i>{{ __('message.common.view') }}
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1" for="description">
                                {{ __('maintenance::message.description') }}
                            </label>
                            <textarea
                                      class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                      name="description" id="description" rows="3"
                                      placeholder="{{ __('maintenance::message.placeholder.enter_description') }}"></textarea>
                            <div class="mt-1 text-sm text-red-500" id="error_description"></div>
                        </div>

                        <div id="user_remark_block" class="hidden">
                            <label class="block text-sm font-medium text-zinc-700 mb-1" for="user_remark">
                                {{ __('message.common.user_remark') }}<span class="text-red-500"> *</span>
                            </label>
                            <textarea required
                                      class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                      name="user_remark" id="user_remark" rows="2"
                                      placeholder="{{ __('message.common.user_remark_placeholder') }}"></textarea>
                            <div class="mt-1 text-sm text-red-500" id="error_user_remark"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 p-4 border-t border-zinc-200">
                        <button type="button" class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center erp-inline-modal-close">
                            {{ __('message.common.cancel') }}
                        </button>
                        <button type="button" id="save" data-route="{{ route('maintenance.store') }}"
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
                <h3 class="text-lg font-semibold text-zinc-900">{{ __('maintenance::message.detail') }}</h3>
                <button type="button" class="text-zinc-400 hover:text-zinc-600 erp-inline-modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div class="flex items-center gap-2 p-3 rounded-md bg-zinc-50 border border-zinc-200 mb-4">
                    <span class="text-sm font-medium text-zinc-500">{{ __('maintenance::message.no') }}:</span>
                    <span class="text-sm font-semibold text-zinc-900" id="view_maintenance_no">-</span>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-medium text-zinc-500">{{ __('maintenance::message.complaint_no') }}</p>
                        <p class="text-sm text-zinc-900" id="view_complaint_no">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500">{{ __('complaint::message.pg') }}</p>
                        <p class="text-sm text-zinc-900" id="view_pg_name">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500">{{ __('complaint::message.room') }}</p>
                        <p class="text-sm text-zinc-900" id="view_room_no">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500">{{ __('maintenance::message.cost') }}</p>
                        <p class="text-sm text-zinc-900" id="view_cost">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500">{{ __('maintenance::message.maintenance_date') }}</p>
                        <p class="text-sm text-zinc-900" id="view_maintenance_date">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500">{{ __('message.common.status') }}</p>
                        <p class="text-sm text-zinc-900" id="view_status">-</p>
                    </div>
                </div>
                <div>
                    <p class="text-xs font-medium text-zinc-500">{{ __('maintenance::message.description') }}</p>
                    <p class="text-sm text-zinc-900" id="view_description">-</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-zinc-500">{{ __('maintenance::message.proof') }}</p>
                    <p class="text-sm text-zinc-900" id="view_proof">-</p>
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
    window.URL_ROUTE = "{{ route('maintenance.index') }}";

    window.validationMessages = {};

    var table = '';
    var pgSelectInst = null;
    var complaintSelectInst = null;
    var statusSelectInst = null;

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
                { data: 'maintenance_no', name: 'maintenance_no' },
                { data: 'complaint_no', name: 'complaint_no', orderable: false, searchable: false },
                { data: 'pg_name', name: 'pg_name', orderable: false, searchable: false },
                { data: 'room_no', name: 'room_no', orderable: false, searchable: false },
                { data: 'cost', name: 'cost', render: function(data) { return data ? '{{ __("message.common.currency_symbol") }}' + parseFloat(data).toFixed(2) : '—'; } },
                { data: 'maintenance_date', name: 'maintenance_date' },
                { data: 'proof_url', name: 'proof_url', orderable: false, searchable: false },
                { data: 'created_user', name: 'created_user', orderable: false, searchable: false },
                { data: 'status', name: 'status', render: function(data) { return data ? data.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) : '-'; } },
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

        // PG → Complaint cascade
        (function() {
            var $pg = $('#pg_id');
            var $complaint = $('#complaint_id');
            if (!$pg.length || !$complaint.length) return;

            var check = setInterval(function() {
                if ($pg.next('.erp-select-wrapper').length) {
                    clearInterval(check);
                    $pg.on('change', function() {
                        var val = $(this).val();
                        if (!val) {
                            if (complaintSelectInst) { complaintSelectInst.setOptions([]); complaintSelectInst.setValue(''); }
                            return;
                        }
                        $.get('{{ route("lookup.complaints-by-pg") }}', { pg_id: val, limit: 9999 }, function(data) {
                            if (!complaintSelectInst) {
                                complaintSelectInst = erpSearchSelect('#complaint_id', { placeholder: '— Select —', allowClear: true });
                            }
                            complaintSelectInst.setOptions(data || []);
                            complaintSelectInst.setValue('');
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
        $('#error_complaint_id').html('');
        $('#error_cost').html('');
        $('#error_maintenance_date').html('');
        $('#error_status').html('');
        $('#error_proof').html('');
        $('#error_description').html('');
        $('#error_user_remark').html('');
        $('#inlineModal').find('.erp-btn-locked').each(function() {
            $(this).css({ opacity: '', pointerEvents: '' }).removeClass('erp-btn-locked').removeData('erp-original-pointer');
        });
        $('#user_remark_block').addClass('hidden');
        $('#existing_proof').addClass('hidden').find('a').attr('href', '');
        $("#save").attr('data-route', "{{ route('maintenance.store') }}")
            .removeClass('update').addClass('save')
            .html('<i class="fa-solid fa-check mr-1.5 text-xs"></i> {{ __("message.common.submit") }}')
            .prop('disabled', false)
            .removeAttr('style')
            .removeData('erp-original-html')
            .removeData('erp-original-style');
        $("#exampleModalTitle").html("{{ __('maintenance::message.detail') }}");

        $.get('{{ route("maintenance.next-no") }}', function(res) {
            $('#display_maintenance_no').text(res.maintenance_no || '—');
        });

        if (pgSelectInst && typeof pgSelectInst.setValue === 'function') {
            pgSelectInst.setValue('');
        }
        if (complaintSelectInst && typeof complaintSelectInst.setOptions === 'function') {
            complaintSelectInst.setOptions([]);
            complaintSelectInst.setValue('');
        }
        if (statusSelectInst && typeof statusSelectInst.setValue === 'function') {
            statusSelectInst.setValue('');
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
        var url = "{{ route('maintenance.show', ':id') }}".replace(':id', id);
        $.ajax({
            type: "GET",
            url: url,
            dataType: 'json',
            success: function(response) {
                if (response.status_code == 200) {
                    var d = response.result;
                    $('#view_maintenance_no').text(d.maintenance_no || '-');
                    $('#view_complaint_no').text(d.complaint ? d.complaint.complaint_no : '-');
                    $('#view_pg_name').text(d.complaint && d.complaint.pg ? d.complaint.pg.pg_name : '-');
                    $('#view_room_no').text(d.complaint && d.complaint.room ? d.complaint.room.room_no : '-');
                    $('#view_cost').text(d.cost ? '{{ __("message.common.currency_symbol") }}' + parseFloat(d.cost).toFixed(2) : '-');
                    $('#view_maintenance_date').text(d.maintenance_date || '-');
                    $('#view_status').text(d.status ? d.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) : '-');
                    $('#view_description').text(d.description || '-');
                    if (d.proof) {
                        $('#view_proof').html('<a href="{{ asset("storage") }}/' + d.proof + '" target="_blank" class="text-zinc-900 underline hover:text-zinc-600"><i class="fa-solid fa-file mr-1"></i>' + d.proof.split('/').pop() + '</a>');
                    } else {
                        $('#view_proof').text('-');
                    }
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
        var url = "{{ route('maintenance.edit', ':id') }}".replace(':id', id);
        $("#save").attr('data-route', "{{ route('maintenance.update', ':id') }}".replace(':id', id));
        $.ajax({
            type: "GET",
            url: url,
            dataType: 'json',
            success: function(response) {
                if (response.status_code == 200) {
                    $("#exampleModalTitle").html("{{ __('maintenance::message.detail') }}");
                    $("#display_maintenance_no").text(response.result.maintenance_no || '—');
                    $("#cost").val(response.result.cost);
                    $("#maintenance_date").val(response.result.maintenance_date);
                    $("#description").val(response.result.description);
                    $("#status").val(response.result.status);
                    $("#id").val(id);
                    $('#user_remark_block').removeClass('hidden');

                    if (pgSelectInst && typeof pgSelectInst.setValue === 'function') {
                        pgSelectInst.setValue(response.result.complaint ? response.result.complaint.pg_id || '' : '');
                    }

                    // Load complaints for the selected PG
                    var pgId = response.result.complaint ? response.result.complaint.pg_id : null;
                    if (pgId) {
                        $.get('{{ route("lookup.complaints-by-pg") }}', { pg_id: pgId, limit: 9999 }, function(data) {
                            if (!complaintSelectInst) {
                                complaintSelectInst = erpSearchSelect('#complaint_id', { placeholder: '— Select —', allowClear: true });
                            }
                            complaintSelectInst.setOptions(data || []);
                            complaintSelectInst.setValue(response.result.complaint_id || '');
                        });
                    }

                    if (response.result.proof) {
                        $('#existing_proof').removeClass('hidden').find('a').attr('href', '{{ asset("storage") }}/' + response.result.proof);
                    }

                    if (statusSelectInst && typeof statusSelectInst.setValue === 'function') {
                        statusSelectInst.setValue(response.result.status || '');
                    } else {
                        $("#status").val(response.result.status);
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
