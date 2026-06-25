@extends('layouts.app-tw')
@section('title', __('noticeboard::message.noticeboard_master'))
@section('nav-module', 'noticeboard')
@section('breadcrumb', 'Home > Noticeboard > Manage Notice')

@section('pagecss')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<style>
  .note-editor .note-toolbar { background: #f8fafc; }
  .note-editor.note-frame { border-color: #e4e4e7; }
</style>
@endsection

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ __('noticeboard::message.noticeboard_list') }}</h1>
    </div>
    <div class="flex items-center gap-2">
        @can('noticeboard-create')
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
                <input type="text" id="filterSearch" name="filter_search" placeholder="{{ __('noticeboard::message.search_placeholder') }}" class="flex-1 min-w-0 bg-transparent px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:outline-none">
            </div>
        </div>
        <div class="lg:col-span-4 flex items-center gap-2 justify-end lg:col-start-9">
            <button type="button" class="search h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800">{{ __('noticeboard::message.apply') }}</button>
            <button type="button" class="reset h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm text-zinc-500 hover:bg-zinc-50">{{ __('noticeboard::message.reset') }}</button>
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
                    <th>{{ __('noticeboard::message.title') }}</th>
                    <th>{{ __('noticeboard::message.pg') }}</th>
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
        <div class="relative w-full max-w-2xl rounded-lg border border-zinc-200 bg-white shadow-xl">
            <div class="flex items-center justify-between p-4 border-b border-zinc-200">
                <h3 class="text-lg font-semibold text-zinc-900" id="exampleModalTitle">{{ __('noticeboard::message.add_noticeboard') }}</h3>
                <button type="button" class="text-zinc-400 hover:text-zinc-600 erp-inline-modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div id="body">
                <form id="form" action="javascript:void(0);" method="POST" novalidate enctype="multipart/form-data">
                    @csrf
                    <div class="p-4 space-y-4">
                        <input type="hidden" name="id" id="id" value="">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="pg_id">
                                    {{ __('noticeboard::message.pg') }}<span class="text-red-500"> *</span>
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
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="title">
                                    {{ __('noticeboard::message.title') }}<span class="text-red-500"> *</span>
                                </label>
                                <input type="text" required
                                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                       name="title" id="title"
                                       placeholder="{{ __('noticeboard::message.enter_title') }}">
                                <div class="mt-1 text-sm text-red-500" id="error_title"></div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-2">{{ __('noticeboard::message.notice_type') }}<span class="text-red-500"> *</span></label>
                            <div class="flex items-center gap-4">
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="notice_type" value="image" checked
                                           class="rounded-full border-zinc-300 text-zinc-900 focus:ring-zinc-900">
                                    <span class="text-sm text-zinc-700">{{ __('noticeboard::message.type_image') }}</span>
                                </label>
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="notice_type" value="text"
                                           class="rounded-full border-zinc-300 text-zinc-900 focus:ring-zinc-900">
                                    <span class="text-sm text-zinc-700">{{ __('noticeboard::message.type_text') }}</span>
                                </label>
                            </div>
                        </div>

                        <div id="notice_image_field">
                            <label class="block text-sm font-medium text-zinc-700 mb-1" for="image">
                                {{ __('noticeboard::message.image') }}
                            </label>
                            <input type="file" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                   class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-zinc-100 file:text-sm file:font-medium hover:file:bg-zinc-200 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                   name="image" id="image">
                            <div id="image_preview" class="mt-2 hidden">
                                <img src="" alt="Preview" class="h-32 w-auto rounded-md border border-zinc-200 object-cover">
                            </div>
                            <div class="mt-1 text-sm text-red-500" id="error_image"></div>
                        </div>

                        <div id="notice_text_field" class="hidden">
                            <label class="block text-sm font-medium text-zinc-700 mb-1" for="description">
                                {{ __('noticeboard::message.description') }}
                            </label>
                            <textarea name="description" id="description" class="hidden"></textarea>
                            <div id="summernote"></div>
                            <div class="mt-1 text-sm text-red-500" id="error_description"></div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                        <button type="button" id="save" data-route="{{ route('noticeboard.store') }}"
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
                <h3 class="text-lg font-semibold text-zinc-900" id="viewModalTitle">{{ __('noticeboard::message.view_noticeboard') }}</h3>
                <button type="button" class="text-zinc-400 hover:text-zinc-600 erp-inline-modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-5 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('noticeboard::message.title') }}</p>
                        <p class="text-base font-semibold text-zinc-900" id="view_title">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('noticeboard::message.pg') }}</p>
                        <p class="text-sm text-zinc-900" id="view_pg_name">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('message.common.created_by') }}</p>
                        <p class="text-sm text-zinc-900" id="view_user_name">-</p>
                    </div>
                    <div class="col-span-2" id="view_image_wrapper" style="display:none;">
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('noticeboard::message.image') }}</p>
                        <img src="" alt="Notice Image" id="view_image" class="h-48 w-auto rounded-md border border-zinc-200 object-cover">
                    </div>
                    <div class="col-span-2">
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('noticeboard::message.description') }}</p>
                        <div class="text-sm text-zinc-900 prose prose-sm max-w-none" id="view_description">-</div>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('message.common.status') }}</p>
                        <p class="text-sm text-zinc-900" id="view_status">-</p>
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
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script type="application/javascript">
    'use strict';
    window.URL_ROUTE = "{{ route('noticeboard.index') }}";

    window.validationMessages = {};

    var table = '';
    var pgSelectInst = null;
    var summernoteInst = null;

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
                { data: 'title', name: 'title' },
                { data: 'pg_name', name: 'pg_name', orderable: false, searchable: false },
                { data: 'user_name', name: 'user_name', orderable: false, searchable: false },
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

        // Image preview
        $('#image').on('change', function() {
            var file = this.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#image_preview img').attr('src', e.target.result);
                    $('#image_preview').removeClass('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                $('#image_preview').addClass('hidden');
            }
        });

        // Radio toggle Image / Text
        $(document).on('change', 'input[name="notice_type"]', function() {
            if ($(this).val() === 'image') {
                $('#notice_image_field').removeClass('hidden');
                $('#notice_text_field').addClass('hidden');
                if (summernoteInst && typeof $('#summernote').summernote === 'function') {
                    $('#summernote').summernote('destroy');
                    summernoteInst = false;
                }
            } else {
                $('#notice_image_field').addClass('hidden');
                $('#notice_text_field').removeClass('hidden');
                initSummernote();
            }
        });

        function initSummernote() {
            if (!summernoteInst) {
                $('#summernote').summernote({
                    height: 200,
                    toolbar: [
                        ['style', ['bold', 'italic', 'underline', 'clear']],
                        ['font', ['strikethrough', 'superscript', 'subscript']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['table', ['table']],
                        ['insert', ['link', 'picture', 'video']],
                        ['view', ['fullscreen', 'codeview', 'help']]
                    ],
                    callbacks: {
                        onChange: function(contents) {
                            $('#description').val(contents);
                        }
                    }
                });
                summernoteInst = true;
            }
        }

        // Init Summernote if text is selected on modal open
        $('#inlineModal').on('shown.bs.modal', function() {
            if ($('input[name="notice_type"]:checked').val() === 'text') {
                initSummernote();
            }
        });
    });

    function resetInlineModal() {
        $('#inlineModal').addClass('hidden');
        $('#form')[0].reset();
        $('#form').find('.border-red-500').removeClass('border-red-500');
        $('#form').find('.erp-field-error').remove();
        $('#form').find('.erp-form-error-banner').hide();
        $('#error_pg_id').html('');
        $('#error_title').html('');
        $('#error_image').html('');
        $('#error_description').html('');
        $('#error_status').html('');
        $('#image_preview').addClass('hidden');
        $('#image_preview img').attr('src', '');
        if (summernoteInst && typeof $('#summernote').summernote === 'function') {
            $('#summernote').summernote('reset');
        }
        $('#description').val('');
        $('input[name="notice_type"][value="image"]').prop('checked', true);
        $('#notice_image_field').removeClass('hidden');
        $('#notice_text_field').addClass('hidden');
        if (summernoteInst && typeof $('#summernote').summernote === 'function') {
            $('#summernote').summernote('destroy');
            summernoteInst = false;
        }
        $('#inlineModal').find('.erp-btn-locked').each(function() {
            $(this).css({ opacity: '', pointerEvents: '' }).removeClass('erp-btn-locked').removeData('erp-original-pointer');
        });
        $("#save").attr('data-route', "{{ route('noticeboard.store') }}")
            .removeClass('update').addClass('save')
            .html('<i class="fa-solid fa-check mr-1.5 text-xs"></i> {{ __("message.common.submit") }}')
            .prop('disabled', false)
            .removeAttr('style')
            .removeData('erp-original-html')
            .removeData('erp-original-style');
        $("#exampleModalTitle").html("{{ __('noticeboard::message.add_noticeboard') }}");

        if (pgSelectInst && typeof pgSelectInst.setValue === 'function') {
            pgSelectInst.setValue('');
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
        var url = "{{ route('noticeboard.show', ':id') }}".replace(':id', id);
        $.ajax({
            type: "GET",
            url: url,
            dataType: 'json',
            success: function(response) {
                if (response.status_code == 200) {
                    var d = response.result;
                    $('#view_title').text(d.title || '-');
                    $('#view_pg_name').text(d.pg?.pg_name || d.pg_name || '-');
                    $('#view_user_name').text(d.user?.name || d.user_name || '-');
                    if (d.image) {
                        var imgUrl = "{{ Storage::url('') }}" + d.image;
                        $('#view_image').attr('src', imgUrl);
                        $('#view_image_wrapper').show();
                    } else {
                        $('#view_image_wrapper').hide();
                    }
                    $('#view_description').html(d.description || '-');
                    $('#view_status').text(d.status ? d.status.charAt(0).toUpperCase() + d.status.slice(1) : '-');
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
        var url = "{{ route('noticeboard.edit', ':id') }}".replace(':id', id);
        $("#save").attr('data-route', "{{ route('noticeboard.update', ':id') }}".replace(':id', id));
        $.ajax({
            type: "GET",
            url: url,
            dataType: 'json',
            success: function(response) {
                if (response.status_code == 200) {
                    $("#exampleModalTitle").html("{{ __('noticeboard::message.edit_noticeboard') }}");
                    $("#title").val(response.result.title);
                    $("#status").val(response.result.status);
                    $("#id").val(id);
                    if (pgSelectInst && typeof pgSelectInst.setValue === 'function') {
                        pgSelectInst.setValue(response.result.pg_id || '');
                    } else {
                        $("#pg_id").val(response.result.pg_id);
                    }
                    if (response.result.description) {
                        $('input[name="notice_type"][value="text"]').prop('checked', true);
                        $('#notice_image_field').addClass('hidden');
                        $('#notice_text_field').removeClass('hidden');
                        $('#description').val(response.result.description);
                    } else {
                        $('input[name="notice_type"][value="image"]').prop('checked', true);
                        $('#notice_image_field').removeClass('hidden');
                        $('#notice_text_field').addClass('hidden');
                    }
                    if (response.result.image) {
                        var imgUrl = "{{ Storage::url('') }}" + response.result.image;
                        $('#image_preview img').attr('src', imgUrl);
                        $('#image_preview').removeClass('hidden');
                    }
                    $('#inlineModal').removeClass('hidden');
                    if (response.result.description) {
                        initSummernote();
                        if (summernoteInst && typeof $('#summernote').summernote === 'function') {
                            $('#summernote').summernote('code', response.result.description);
                        }
                    }
                } else if (response.status_code == 201 || response.status_code == 404) {
                    toastr.warning(response.message, "Warning");
                } else {
                    toastr.error(response.message, "Error");
                }
            },
            error: function(xhr) {
                var msg = 'Something went wrong. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                toastr.error(msg, "Error");
            }
        });
    });
</script>
@endsection
