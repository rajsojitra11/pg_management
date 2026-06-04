{{--
    User Remarks Field Partial — Tailwind Theme

    Usage:
    @include('partials-tw.remarks-field')
    @include('partials-tw.remarks-field', ['type' => 'update'])
    @include('partials-tw.remarks-field', ['type' => 'delete', 'value' => $existingRemark])
    @include('partials-tw.remarks-field', ['required' => false])

    Parameters:
    - type: 'create', 'update', 'delete', or 'custom' (optional, defaults to 'create')
    - value: Current value (optional)
    - required: Whether field is required (optional, auto-determined by type)
    - colSize: Tailwind width class (optional, defaults to 'w-full')
    - rows: Number of textarea rows (optional, defaults to 3)
    - minlength: Minimum character length (optional, defaults to config value for required fields)
    - maxlength: Maximum character length (optional, defaults to config value)
    - placeholder: Custom placeholder text (optional)
    - label: Custom label text (optional)
    - helpText: Custom help text (optional)
    - fieldId: Custom field ID (optional)
    - fieldName: Custom field name (optional, defaults to 'user_remark')
--}}

@php
    $fieldType = $type ?? 'create';
    $colClass = $colSize ?? 'w-full';
    $textareaRows = $rows ?? 3;
    $minLength = $minlength ?? config('app.min_comment_length', 3);
    $maxLength = $maxlength ?? config('app.max_comment_length', 1000);
    $fieldName = $fieldName ?? 'user_remark';
    $skipDynamic = $skipDynamicValidation ?? false;

    $isRequired = $required ?? in_array($fieldType, ['update', 'delete']);

    $defaultLabels = [
        'create' => __('lang.common.user_remark') ?: 'User Remark',
        'update' => __('lang.common.user_remark') ?: 'User Remark',
        'delete' => __('lang.common.deletion_reason') ?: 'Deletion Reason',
        'custom' => __('lang.common.user_remark') ?: 'User Remark',
    ];

    $defaultPlaceholders = [
        'create' => __('lang.placeholders.user_remark_create') ?: 'Optional: Provide additional context for this entry...',
        'update' => __('lang.placeholders.user_remark_update') ?: 'Please explain why you are making this change...',
        'delete' => __('lang.placeholders.user_remark_delete') ?: 'Please explain why you are deleting this record...',
        'custom' => __('lang.placeholders.user_remark_custom') ?: 'Please provide your remarks...',
    ];

    $defaultHelpTexts = [
        'create' => __('lang.common.user_remark_help_create') ?: 'Optional: You can provide additional context for this entry',
        'update' => __('lang.common.user_remark_required_update') ?: 'Please provide a reason for this update',
        'delete' => __('lang.common.user_remark_required_delete') ?: 'Please provide a reason for deletion',
        'custom' => __('lang.common.user_remark_help_custom') ?: 'Please provide your remarks',
    ];

    $fieldLabel = $label ?? $defaultLabels[$fieldType];
    $fieldPlaceholder = $placeholder ?? $defaultPlaceholders[$fieldType];
    $fieldHelpText = $helpText ?? $defaultHelpTexts[$fieldType];

    if (isset($fieldId)) {
        $finalFieldId = $fieldId;
    } else {
        $finalFieldId = $fieldType === 'delete' ? 'delete_user_remark' : 'user_remark';
    }
@endphp

<div class="{{ $colClass }} mb-3">
    <label class="block text-sm font-medium text-zinc-700 mb-1" for="{{ $finalFieldId }}">
        {{ $fieldLabel }}
        @if ($isRequired)
            <span class="text-red-500">*</span>
        @endif
    </label>

    <textarea class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500 {{ $skipDynamic ? '' : 'remarks-field' }}"
        id="{{ $finalFieldId }}"
        name="{{ $fieldName }}"
        rows="{{ $textareaRows }}"
        placeholder="{{ $fieldPlaceholder }}"
        data-field-id="{{ $finalFieldId }}"
        data-field-type="{{ $fieldType }}"
        data-is-required="{{ $isRequired ? 'true' : 'false' }}"
        data-min-length="{{ $minLength }}"
        data-max-length="{{ $maxLength }}">{{ old($fieldName, $value ?? '') }}</textarea>

    <p class="mt-1 text-xs text-zinc-500">{{ $fieldHelpText }}</p>

    @if ($isRequired)
        <p class="mt-0.5 text-xs text-zinc-500">
            {{ __('lang.reason_required_min_3') ?: 'Minimum ' . $minLength . ' characters required' }}
        </p>
    @endif

    <div class="mt-1 text-sm text-red-500" id="error_{{ $finalFieldId }}"></div>
</div>
