@extends('layouts.app-tw')
@section('title', __('role::message.add'))
@section('nav-module', 'roles')
@section('breadcrumb', __('role::message.add'))

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h5 class="text-lg font-semibold text-zinc-900">{{ __('role::message.add') }}</h5>
        <p class="text-sm mt-0.5" style="color: var(--erp-text-secondary);">Define a new role with specific module permissions</p>
    </div>
    @can('role-list')
    <a href="{{ route('roles.index') }}" class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center">
        <i class="fa-solid fa-arrow-left mr-1.5 text-xs"></i> {{ __('message.common.back') }}
    </a>
    @endcan
</div>

<form action="{{ route('roles.store') }}" method="POST" id="roleForm" novalidate>
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
        {{-- Role Name --}}
        <div class="p-5">
            <label class="block text-sm font-medium text-zinc-700 mb-1.5" for="roleName">
                {{ __('role::message.name') }} <span class="text-red-500">*</span>
            </label>
            <input type="text" name="name" id="roleName" required
                   class="w-full sm:w-96 h-9 rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500"
                   placeholder="e.g. Editor" value="{{ old('name') }}">
            @if ($errors->has('name'))
                <p class="mt-1.5 text-sm text-red-500">{{ $errors->first('name') }}</p>
            @endif
            <p class="mt-1.5 text-xs" style="color: var(--erp-text-tertiary);">Use underscores instead of spaces (e.g. Inventory_Manager)</p>
        </div>

        {{-- Year Access Card --}}
        <div class="p-5 border-t border-zinc-200">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-base font-semibold text-zinc-900">Year Access</h2>
                    <p class="text-sm text-zinc-500 mt-0.5">Restrict which financial years users with this role can view data for</p>
                </div>
                <span class="inline-flex items-center rounded-md border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700 whitespace-nowrap">
                    <i class="fa-solid fa-shield-halved mr-1 text-[10px]"></i> Access Control
                </span>
            </div>
            <div class="flex items-start gap-3 p-3 rounded-md border border-zinc-200 bg-zinc-50 mb-4">
                <input type="checkbox" name="all_years" id="allYears" value="1" class="mt-0.5 h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900">
                <div class="flex-1">
                    <label for="allYears" class="text-sm font-medium text-zinc-900 cursor-pointer">Grant access to all financial years</label>
                    <p class="text-xs text-zinc-500 mt-0.5">Recommended only for Admin / Super Admin roles. Users will see records across every year.</p>
                </div>
            </div>
            <div id="yearsBoxWrap">
                <label class="block text-sm font-medium text-zinc-700 mb-1.5">Year Access Range <span class="text-red-500">*</span></label>
                <input type="number" name="allowed_year" id="yearsBox" min="1" step="1" inputmode="numeric" autocomplete="off"
                    placeholder="e.g., 1" value="{{ old('allowed_year') }}"
                    class="h-9 w-full sm:w-96 rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500">
                <p class="mt-1.5 text-xs text-zinc-500">Total years of data to include (e.g., <span class="font-medium">1</span> = current year only, <span class="font-medium">2</span> = current + past 1 year).</p>
            </div>
        </div>

        {{-- Permissions --}}
        <div class="p-5 border-t border-zinc-200">
            @include('role::partials.permission-grid', ['permission' => $permission, 'rolePermissions' => []])

            @if ($errors->has('permission'))
                <div class="mt-3 rounded-md border border-red-200 bg-red-50 px-4 py-2.5">
                    <p class="text-sm text-red-600"><i class="fa-solid fa-circle-exclamation mr-1.5 text-xs"></i>{{ $errors->first('permission') }}</p>
                </div>
            @endif
        </div>

        {{-- Actions --}}
        <div class="p-5 border-t border-zinc-200 flex items-center justify-between">
            <a href="{{ route('roles.index') }}" class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center gap-2 transition-colors">
                <i class="fa-solid fa-xmark text-xs"></i> {{ __('message.common.cancel') }}
            </a>
            <button type="submit" class="h-9 px-4 rounded-md text-sm font-medium whitespace-nowrap inline-flex items-center gap-2 transition-colors" style="background-color: var(--erp-primary); color: var(--erp-primary-fg);">
                <i class="fa-solid fa-check text-xs"></i> {{ __('message.common.submit') }}
            </button>
        </div>
    </div>
</form>
@endsection

@section('pagescript')
<script>
$(document).ready(function() {
    // Year Access: allYears checkbox toggles the input
    $('#allYears').on('change', function() {
        if (this.checked) {
            $('#yearsBox').val('');
            $('#yearsBox').prop('disabled', true);
            $('#yearsBoxWrap').addClass('opacity-50 pointer-events-none');
        } else {
            $('#yearsBox').prop('disabled', false);
            $('#yearsBoxWrap').removeClass('opacity-50 pointer-events-none');
        }
    });

    // Select All
    $('#selectAll').on('change', function() {
        var isChecked = $(this).is(':checked');
        $('.parent-checkbox').prop('checked', isChecked);
        $('.child-checkbox').prop('checked', isChecked);
        if (typeof syncChipState === 'function') syncChipState();
    });

    // Parent → children
    $(document).on('change', '.parent-checkbox', function() {
        var childClass = $(this).data('child');
        $('.' + childClass).prop('checked', $(this).is(':checked'));
        var schild = $('.s-child');
        $('#selectAll').prop('checked', schild.length === schild.filter(':checked').length);
        if (typeof syncChipState === 'function') syncChipState();
    });

    // Child → parent + selectAll
    $(document).on('change', '.child-checkbox', function() {
        var parentName = $(this).data('parent');
        var childCheckboxes = $('.child_' + parentName);
        $('#all_' + parentName).prop('checked', childCheckboxes.length === childCheckboxes.filter(':checked').length);
        var schild = $('.s-child');
        $('#selectAll').prop('checked', schild.length === schild.filter(':checked').length);
        if (typeof syncChipState === 'function') syncChipState();
    });

    // Show server validation errors inline next to each field
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

    // AJAX form submit with validation
    $('#roleForm').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('[type="submit"]');

        var errors = validateFormFields($form);
        if (errors.length > 0) { setButtonError($btn); return false; }

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
                    if (response.errors) showInlineServerErrors($form, response.errors);
                    else showFormError($form, response.message || 'Something went wrong');
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
