@extends('layouts.app-tw')
@section('title', __('email::message.module_name'))
@section('nav-module', 'email')
@section('breadcrumb', 'Home > Email > Settings')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ __('email::message.title') }}</h1>
    </div>
</div>

{{-- Tabs --}}
<div class="mb-6 border-b border-zinc-200">
    <nav class="flex gap-6">
        <button type="button" class="tab-btn text-sm font-medium pb-3 border-b-2 border-zinc-900 text-zinc-900 transition" data-tab="config">
            {{ __('email::message.config_tab') }}
        </button>
        <button type="button" class="tab-btn text-sm font-medium pb-3 border-b-2 border-transparent text-zinc-500 hover:text-zinc-700 transition" data-tab="template">
            {{ __('email::message.template_tab') }}
        </button>
    </nav>
</div>

{{-- Tab: Config --}}
<div id="tab-config" class="tab-content">
    <div class="flex items-center justify-end mb-4">
        @can('email-create')
        <button type="button" class="h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 whitespace-nowrap inline-flex items-center" onclick="resetConfigModal();$('#configModal').removeClass('hidden')">
            <i class="fa-solid fa-plus mr-1.5 text-xs"></i> {{ __('message.common.addNew') }}
        </button>
        @endcan
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
        <div class="p-4 overflow-x-auto">
            <table id="configTable" class="display responsive nowrap w-full">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('email::message.pg') }}</th>
                        <th>{{ __('email::message.sender_email') }}</th>
                        <th>{{ __('email::message.sender_name') }}</th>
                        <th>{{ __('email::message.subject_prefix') }}</th>
                        <th>{{ __('message.common.status') }}</th>
                        <th>{{ __('message.common.created_by') }}</th>
                        <th>{{ __('message.common.action') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

{{-- Tab: Template --}}
<div id="tab-template" class="tab-content hidden">
    <div class="rounded-lg border border-zinc-200 bg-white shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-zinc-900">{{ __('email::message.template_title') }}</h3>
            <button type="button" id="previewTemplate"
                    class="h-9 px-4 rounded-md border border-zinc-300 text-zinc-700 text-sm font-medium hover:bg-zinc-50 inline-flex items-center">
                <i class="fa-solid fa-eye mr-1.5 text-xs"></i>
                {{ __('email::message.preview') }}
            </button>
        </div>

        <form id="templateForm" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1" for="template_subject">
                        {{ __('email::message.template_subject') }}<span class="text-red-500"> *</span>
                    </label>
                    <input type="text" id="template_subject" name="subject"
                           class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                           placeholder="{{ __('email::message.placeholder.enter_subject') }}">
                    <div class="mt-1 text-sm text-red-500" id="error_subject"></div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1" for="template_body">
                        {{ __('email::message.template_body') }}<span class="text-red-500"> *</span>
                    </label>
                    <textarea id="template_body" name="body" rows="15"
                              class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 font-mono focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                              placeholder="{{ __('email::message.placeholder.enter_body') }}"></textarea>
                    <div class="mt-1 text-sm text-red-500" id="error_body"></div>
                </div>

                <div class="rounded-md bg-zinc-50 border border-zinc-200 p-4">
                    <p class="text-sm font-medium text-zinc-700 mb-2">{{ __('email::message.placeholders_info') }}</p>
                    <div class="grid grid-cols-2 gap-2 text-xs text-zinc-600">
                        <code class="bg-white px-2 py-1 rounded border border-zinc-200">{tenant_name}</code><span>{{ __('email::message.placeholder_tenant_name') }}</span>
                        <code class="bg-white px-2 py-1 rounded border border-zinc-200">{tenant_email}</code><span>{{ __('email::message.placeholder_tenant_email') }}</span>
                        <code class="bg-white px-2 py-1 rounded border border-zinc-200">{pg_name}</code><span>{{ __('email::message.placeholder_pg_name') }}</span>
                        <code class="bg-white px-2 py-1 rounded border border-zinc-200">{room_no}</code><span>{{ __('email::message.placeholder_room_no') }}</span>
                        <code class="bg-white px-2 py-1 rounded border border-zinc-200">{checkin_date}</code><span>{{ __('email::message.placeholder_checkin_date') }}</span>
                        <code class="bg-white px-2 py-1 rounded border border-zinc-200">{monthly_rent}</code><span>{{ __('email::message.placeholder_monthly_rent') }}</span>
                        <code class="bg-white px-2 py-1 rounded border border-zinc-200">{due_date}</code><span>{{ __('email::message.placeholder_due_date') }}</span>
                        <code class="bg-white px-2 py-1 rounded border border-zinc-200">{current_month}</code><span>{{ __('email::message.placeholder_current_month') }}</span>
                        <code class="bg-white px-2 py-1 rounded border border-zinc-200">{sender_name}</code><span>{{ __('email::message.placeholder_sender_name') }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" id="saveTemplate"
                            class="h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 inline-flex items-center">
                        <i class="fa-solid fa-check mr-1.5 text-xs"></i>
                        {{ __('message.common.save') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Config Modal --}}
<div id="configModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 erp-modal-close"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="relative w-full max-w-lg rounded-lg border border-zinc-200 bg-white shadow-xl">
            <div class="flex items-center justify-between p-4 border-b border-zinc-200">
                <h3 class="text-lg font-semibold text-zinc-900" id="configModalTitle">{{ __('email::message.add_config') }}</h3>
                <button type="button" class="text-zinc-400 hover:text-zinc-600 erp-modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div id="configBody">
                <form id="configForm" method="POST" novalidate>
                    @csrf
                    <div class="p-4 space-y-4">
                        <input type="hidden" name="id" id="config_id" value="">

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1" for="pg_id">
                                {{ __('email::message.pg') }}<span class="text-red-500"> *</span>
                            </label>
                            <select name="pg_id" id="pg_id" required style="width:100%;">
                                <option value="">{{ __('email::message.placeholder.select_pg') }}</option>
                                @foreach ($pgList as $pg)
                                <option value="{{ $pg->id }}">{{ $pg->pg_name }}</option>
                                @endforeach
                            </select>
                            <div class="mt-1 text-sm text-red-500" id="error_pg_id"></div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1" for="sender_email">
                                {{ __('email::message.sender_email') }}<span class="text-red-500"> *</span>
                            </label>
                            <input type="email" required
                                   class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                   name="sender_email" id="sender_email"
                                   placeholder="{{ __('email::message.placeholder.enter_email') }}">
                            <div class="mt-1 text-sm text-red-500" id="error_sender_email"></div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1" for="sender_name">
                                {{ __('email::message.sender_name') }}
                            </label>
                            <input type="text"
                                   class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                   name="sender_name" id="sender_name"
                                   placeholder="{{ __('email::message.placeholder.enter_name') }}">
                            <div class="mt-1 text-sm text-red-500" id="error_sender_name"></div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1" for="subject_prefix">
                                {{ __('email::message.subject_prefix') }}
                            </label>
                            <input type="text"
                                   class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                   name="subject_prefix" id="subject_prefix"
                                   placeholder="{{ __('email::message.placeholder.enter_prefix') }}">
                            <div class="mt-1 text-sm text-red-500" id="error_subject_prefix"></div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1" for="config_status">
                                {{ __('message.common.status') }}<span class="text-red-500"> *</span>
                            </label>
                            <select name="status" id="config_status" required style="width:100%;">
                                <option value="active">{{ __('message.common.active') }}</option>
                                <option value="inactive">{{ __('message.common.inactive') }}</option>
                            </select>
                            <div class="mt-1 text-sm text-red-500" id="error_status"></div>
                        </div>

                        <div id="config_user_remark_block" class="hidden">
                            <label class="block text-sm font-medium text-zinc-700 mb-1" for="config_user_remark">
                                {{ __('message.common.user_remark') }}<span class="text-red-500"> *</span>
                            </label>
                            <textarea required
                                      class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                      name="user_remark" id="config_user_remark" rows="2"
                                      placeholder="{{ __('message.common.user_remark_placeholder') }}"></textarea>
                            <div class="mt-1 text-sm text-red-500" id="error_user_remark"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 p-4 border-t border-zinc-200">
                        <button type="button" class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 erp-modal-close">
                            {{ __('message.common.cancel') }}
                        </button>
                        <button type="button" id="saveConfig"
                                class="h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 inline-flex items-center">
                            <i class="fa-solid fa-check mr-1.5 text-xs"></i>
                            {{ __('message.common.submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

{{-- Preview Modal --}}
<div id="previewModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 preview-modal-close"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="relative w-full max-w-3xl rounded-lg border border-zinc-200 bg-white shadow-xl">
            <div class="flex items-center justify-between p-4 border-b border-zinc-200">
                <h3 class="text-lg font-semibold text-zinc-900">{{ __('email::message.preview') }}</h3>
                <button type="button" class="text-zinc-400 hover:text-zinc-600 preview-modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="p-4 max-h-[70vh] overflow-y-auto">
                <div class="mb-3 pb-3 border-b border-zinc-200">
                    <span class="text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ __('email::message.template_subject') }}:</span>
                    <p id="previewSubject" class="text-sm font-medium text-zinc-900 mt-1"></p>
                </div>
                <div id="previewBody" class="preview-body"></div>
            </div>
            <div class="flex items-center justify-end gap-2 p-4 border-t border-zinc-200">
                <button type="button" class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 preview-modal-close">
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

    var configTable = null;
    var pgSelectInst = null;
    var statusSelectInst = null;

    // Tab switching
    $(document).on('click', '.tab-btn', function() {
        var tab = $(this).data('tab');
        $('.tab-btn').removeClass('border-zinc-900 text-zinc-900').addClass('border-transparent text-zinc-500');
        $(this).addClass('border-zinc-900 text-zinc-900').removeClass('border-transparent text-zinc-500');
        $('.tab-content').addClass('hidden');
        $('#tab-' + tab).removeClass('hidden');
    });

    $(function() {
        if (typeof initErpSelect === 'function') {
            pgSelectInst = initErpSelect('#pg_id', { allowClear: true, placeholder: '{{ __("email::message.placeholder.select_pg") }}' });
        }

        configTable = initErpTable('#configTable', {
            ajax: {
                url: "{{ route('email.config-data') }}",
                data: function(d) {}
            },
            processing: true,
            serverSide: true,
            scrollX: true,
            aLengthMenu: [[15, 30, 50, 100, -1], [15, 30, 50, 100, "All"]],
            order: [[0, 'desc']],
            columns: [
                { data: 'id', render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; }, orderable: false, width: '50px' },
                { data: 'pg_name', name: 'pg_name', orderable: false, searchable: false },
                { data: 'sender_email', name: 'sender_email' },
                { data: 'sender_name', name: 'sender_name' },
                { data: 'subject_prefix', name: 'subject_prefix' },
                { data: 'status', name: 'status' },
                { data: 'created_user', name: 'created_user', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, sortable: false, width: '160px' }
            ]
        });

        // ── Template load ─────────────────────────────────────────────
        $.get("{{ route('email.template.get') }}", function(res) {
            if (res.result) {
                $('#template_subject').val(res.result.subject || '');
                $('#template_body').val(res.result.body || '');
            }
        });

        // ── Preview template ─────────────────────────────────────────
        $(document).on('click', '#previewTemplate', function() {
            var btn = $(this);
            var data = {
                _token: '{{ csrf_token() }}',
                subject: $('#template_subject').val(),
                body: $('#template_body').val()
            };

            if (!data.subject || !data.body) {
                erpToast({ title: 'Warning', message: '{{ __("email::message.preview_required") }}', type: 'warning' });
                return;
            }

            btn.prop('disabled', true).html('{{ __("email::message.previewing") }}');

            $.ajax({
                type: 'POST',
                url: "{{ route('email.template.preview') }}",
                data: data,
                dataType: 'json',
                success: function(res) {
                    if (res.status_code == 200) {
                        $('#previewSubject').text(res.subject);
                        $('#previewBody').html(res.body);
                        $('#previewModal').removeClass('hidden');
                    } else {
                        erpToast({ title: 'Error', message: res.message || 'Something went wrong', type: 'error' });
                    }
                },
                error: function(xhr) {
                    var msg = 'Something went wrong';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    erpToast({ title: 'Error', message: msg, type: 'error' });
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="fa-solid fa-eye mr-1.5 text-xs"></i> {{ __("email::message.preview") }}');
                }
            });
        });

        $(document).on('click', '.preview-modal-close', function(e) {
            e.preventDefault();
            $('#previewModal').addClass('hidden');
        });

        // ── Save template ─────────────────────────────────────────────
        $(document).on('click', '#saveTemplate', function() {
            var btn = $(this);
            var data = {
                _token: '{{ csrf_token() }}',
                subject: $('#template_subject').val(),
                body: $('#template_body').val()
            };
            btn.prop('disabled', true).html('{{ __("message.common.saving") }}');

            $.ajax({
                type: 'POST',
                url: "{{ route('email.template.save') }}",
                data: data,
                dataType: 'json',
                success: function(res) {
                    if (res.status_code == 200) {
                        erpToast({ title: 'Success', message: res.message, type: 'success' });
                    } else {
                        erpToast({ title: 'Error', message: res.message || 'Something went wrong', type: 'error' });
                    }
                },
                error: function(xhr) {
                    var msg = 'Something went wrong';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    erpToast({ title: 'Error', message: msg, type: 'error' });
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="fa-solid fa-check mr-1.5 text-xs"></i> {{ __("message.common.save") }}');
                }
            });
        });

        // ── Save config ───────────────────────────────────────────────
        $(document).on('click', '#saveConfig', function() {
            var btn = $(this);
            var id = $('#config_id').val();
            var isUpdate = id ? true : false;
            var url = isUpdate ? "{{ route('email.config.update', ':id') }}".replace(':id', id) : "{{ route('email.config.store') }}";
            var method = isUpdate ? 'PUT' : 'POST';

            var data = {
                _token: '{{ csrf_token() }}',
                pg_id: $('#pg_id').val(),
                sender_email: $('#sender_email').val(),
                sender_name: $('#sender_name').val(),
                subject_prefix: $('#subject_prefix').val(),
                status: $('#config_status').val(),
                user_remark: $('#config_user_remark').val()
            };

            btn.prop('disabled', true).html('{{ __("message.common.saving") }}');

            $.ajax({
                type: method,
                url: url,
                data: data,
                dataType: 'json',
                success: function(res) {
                    if (res.status_code == 200) {
                        erpToast({ title: 'Success', message: res.message, type: 'success' });
                        resetConfigModal();
                        configTable.ajax.reload();
                    } else {
                        erpToast({ title: 'Error', message: res.message || 'Something went wrong', type: 'error' });
                    }
                },
                error: function(xhr) {
                    var msg = 'Something went wrong';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    erpToast({ title: 'Error', message: msg, type: 'error' });
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="fa-solid fa-check mr-1.5 text-xs"></i> {{ __("message.common.submit") }}');
                }
            });
        });
    });

    function resetConfigModal() {
        $('#configModal').addClass('hidden');
        $('#configForm')[0].reset();
        $('#config_id').val('');
        $('#config_user_remark_block').addClass('hidden');
        $('#config_user_remark').val('');
        $('#configModalTitle').text("{{ __('email::message.add_config') }}");
        $('#saveConfig').html('<i class="fa-solid fa-check mr-1.5 text-xs"></i> {{ __("message.common.submit") }}');
        if (pgSelectInst && typeof pgSelectInst.setValue === 'function') pgSelectInst.setValue('');
        if (statusSelectInst && typeof statusSelectInst.setValue === 'function') statusSelectInst.setValue('active');
        $('#error_pg_id, #error_sender_email, #error_sender_name, #error_subject_prefix, #error_status, #error_user_remark').html('');
    }

    $(document).on('click', '.erp-modal-close', function(e) {
        e.preventDefault();
        resetConfigModal();
    });

    $(document).on('click', '.edit', function(e) {
        e.preventDefault();
        resetConfigModal();
        var id = $(this).data('id');
        var url = "{{ route('email.config.edit', ':id') }}".replace(':id', id);
        $('#saveConfig').html('<i class="fa-solid fa-check mr-1.5 text-xs"></i> {{ __("message.common.update") }}');
        $('#configModalTitle').text("{{ __('email::message.edit_config') }}");
        $('#config_id').val(id);
        $('#config_user_remark_block').removeClass('hidden');

        $.get(url, function(res) {
            if (res.status_code == 200) {
                var d = res.result;
                if (pgSelectInst && typeof pgSelectInst.setValue === 'function') {
                    pgSelectInst.setValue(d.pg_id || '');
                } else {
                    $('#pg_id').val(d.pg_id);
                }
                $('#sender_email').val(d.sender_email);
                $('#sender_name').val(d.sender_name || '');
                $('#subject_prefix').val(d.subject_prefix || '');
                $('#config_status').val(d.status || 'active');
                $('#configModal').removeClass('hidden');
            } else {
                erpToast({ title: 'Warning', message: res.message || 'Config not found', type: 'warning' });
            }
        });
    });
</script>
@endsection
