@extends('layouts.app-tw')
@section('title', __('unit::message.add'))
@section('nav-module', 'unit')
@section('breadcrumb', 'Home > Masters > Unit > Add')

@section('content')
{{-- Page Header --}}
<div class="flex items-center justify-between mb-6">
    <h5 class="text-lg font-semibold text-zinc-700">{{ __('unit::message.add') }}</h5>
    @can('unit-list')
    <a href="{{ route('unit.index') }}" class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center">
        <i class="fa-solid fa-arrow-left mr-1.5 text-xs"></i> {{ __('message.common.back') }}
    </a>
    @endcan
</div>

<form action="{{ route('unit.store') }}" method="POST" id="unitForm" novalidate>
    @csrf

    {{-- Server-side error banner --}}
    <div class="erp-form-error-banner rounded-lg border border-red-200 bg-red-50 p-3 mb-4" style="display:none;">
        <div class="flex items-start gap-2">
            <i class="fa-solid fa-circle-exclamation text-red-500 mt-0.5"></i>
            <p class="text-sm text-red-700 erp-form-error-text"></p>
        </div>
    </div>

    {{-- Single Form Card --}}
    <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
        <div class="p-5">
            <div class="grid grid-cols-12 gap-4">
                {{-- Unit Name --}}
                <div class="col-span-12 sm:col-span-8">
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5" for="unit_name">
                        {{ __('unit::message.name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" required name="name" id="unit_name"
                           class="w-full h-9 rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500"
                           placeholder="{{ __('unit::message.enter_name') }}">
                    <div class="mt-1 text-sm text-red-500" id="error_name"></div>
                </div>

                {{-- Unit Value --}}
                <div class="col-span-12 sm:col-span-4">
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5" for="unit_value">
                        {{ __('unit::message.unit_value') }}
                    </label>
                    <input type="text" name="unit_value" id="unit_value" value="1"
                           class="w-full h-9 rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500">
                </div>
            </div>
        </div>

        {{-- Footer Buttons --}}
        <div class="p-5 border-t border-zinc-200 flex items-center justify-between">
            <a href="{{ route('unit.index') }}" class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center">
                <i class="fa-solid fa-xmark mr-1.5 text-xs"></i> {{ __('message.common.cancel') }}
            </a>
            <button type="submit" class="h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 inline-flex items-center">
                <i class="fa-solid fa-check mr-1.5 text-xs"></i> {{ __('message.common.submit') }}
            </button>
        </div>
    </div>
</form>
@endsection

@section('pagescript')
<script type="application/javascript">
    'use strict';

    // ── Show server validation errors inline ──
    function showInlineServerErrors($form, errors) {
        var firstField = null;
        $.each(errors, function(field, messages) {
            var msg = Array.isArray(messages) ? messages[0] : messages;
            var $field = $form.find('[name="' + field + '"]');
            if ($field.length) {
                addFieldError($field, msg);
                if (!firstField) firstField = $field;
            } else {
                showFormError($form, msg);
            }
        });
        if (firstField) {
            firstField[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    // ── AJAX form submit ──
    $('#unitForm').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('[type="submit"]');

        var errors = validateFormFields($form);
        if (errors.length > 0) {
            setButtonError($btn);
            return false;
        }

        $form.find('.erp-field-error').remove();
        $form.find('.border-red-500').removeClass('border-red-500');
        $form.find('.erp-form-error-banner').hide();
        setButtonLoading($btn);

        $.ajax({
            type: 'POST',
            url: $form.attr('action'),
            data: new FormData($form[0]),
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function(response) {
                if (response.status_code == 200) {
                    erpToast({ title: 'Success', message: response.message, type: 'success' });
                    if (response.data) {
                        setTimeout(function() { location.href = response.data; }, 500);
                    }
                } else {
                    setButtonError($btn);
                    showFormError($form, response.message || 'Something went wrong');
                }
            },
            error: function(xhr) {
                setButtonError($btn);
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    showInlineServerErrors($form, xhr.responseJSON.errors);
                } else {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong. Please try again.';
                    showFormError($form, msg);
                }
            }
        });
    });
</script>
@endsection
