@php
    $fieldName = $fieldName ?? 'remark';
    $fieldId = $fieldId ?? $fieldName;
    $type = $type ?? 'create';
    $label = $label ?? ucfirst(str_replace('_', ' ', $fieldName));
    $placeholder = $placeholder ?? '';
    $required = $required ?? false;
    $rows = $rows ?? 3;
@endphp
<div>
    <label class="block text-sm font-medium text-zinc-700 mb-1.5" for="{{ $fieldId }}">
        {{ $label }}
        @if ($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
    <textarea name="{{ $fieldName }}" id="{{ $fieldId }}" rows="{{ $rows }}"
              class="w-full rounded-md border border-zinc-200 bg-transparent px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
              @if ($placeholder) placeholder="{{ $placeholder }}" @endif
              @if ($required) required @endif>{{ old($fieldName, '') }}</textarea>
    <div class="mt-1 text-xs text-red-500 erp-field-error" id="error_{{ $fieldId }}"></div>
</div>
