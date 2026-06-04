@extends('layouts.app-tw')
@section('title', __('country::message.add'))
@section('nav-module', 'country')
@section('breadcrumb', 'Home > Masters > Country > Create')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h5 class="text-lg font-semibold" style="color: var(--erp-text);">{{ __('country::message.add') }}</h5>
    @can('country-list')
    <a href="{{ route('country.index') }}" class="erp-modal-btn-secondary">
        <i class="fa-solid fa-arrow-left mr-1.5 text-xs"></i> {{ __('message.common.back') }}
    </a>
    @endcan
</div>

<form action="{{ route('country.store') }}" method="POST" id="countryForm" novalidate>
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
        {{-- Form Fields --}}
        <div class="p-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Name --}}
                <div>
                    <label class="block text-sm font-medium mb-1" for="country_name" style="color: var(--erp-text);">
                        {{ __('country::message.name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="country_name" required
                           class="w-full h-9 rounded-md border px-3 text-sm focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500"
                           style="border-color: var(--erp-border); background-color: var(--erp-bg); color: var(--erp-text); --tw-ring-color: var(--erp-primary);"
                           placeholder="{{ __('country::message.enter_name') }}" value="{{ old('name') }}">
                </div>
                {{-- Code --}}
                <div>
                    <label class="block text-sm font-medium mb-1" for="country_code" style="color: var(--erp-text);">
                        {{ __('country::message.code') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="code" id="country_code" required maxlength="10"
                           class="w-full h-9 rounded-md border px-3 text-sm focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500"
                           style="border-color: var(--erp-border); background-color: var(--erp-bg); color: var(--erp-text); --tw-ring-color: var(--erp-primary);"
                           placeholder="{{ __('country::message.code') }}" value="{{ old('code') }}">
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="p-5 border-t border-zinc-200 flex items-center justify-between">
            <a href="{{ route('country.index') }}" class="erp-modal-btn-secondary">
                <i class="fa-solid fa-xmark mr-1.5 text-xs"></i> {{ __('message.common.cancel') }}
            </a>
            <button type="submit" class="erp-modal-btn-primary">
                <i class="fa-solid fa-check mr-1.5 text-xs"></i> {{ __('message.common.submit') }}
            </button>
        </div>
    </div>
</form>
@endsection

@section('pagescript')
<script>
$(document).ready(function() {
    function showInlineServerErrors($form, errors) {
        var firstField = null;
        $.each(errors, function(field, messages) {
            var msg = Array.isArray(messages) ? messages[0] : messages;
            var $field = $form.find('[name="' + field + '"]');
            if (!$field.length) $field = $form.find('[name="' + field + '[]"]');
            if ($field.length) {
                addFieldError($field, msg);
                if (!firstField) firstField = $field;
            } else {
                showFormError($form, msg);
            }
        });
        if (firstField) firstField[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    $('#countryForm').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('[type="submit"]');

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
                    if (response.data) setTimeout(function() { location.href = response.data; }, 500);
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
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong.';
                    showFormError($form, msg);
                }
            }
        });
    });
});
</script>
@endsection
