@extends('layouts.app-tw')
@section('title', __('lang.labels.env_variable.edit'))
@section('nav-module', 'envvariable')
@section('breadcrumb', 'Home > Env Variables > Edit')

@section('content')
{{-- Page Header --}}
<div class="flex items-center justify-between mb-6">
    <h5 class="text-lg font-semibold text-zinc-900">{{ __('lang.labels.env_variable.edit') }}</h5>
    <div class="flex items-center gap-2">
        @can('env-variable-list')
        <a href="{{ route('env-variable.index') }}"
           class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center gap-2">
            <i class="fa-solid fa-arrow-left text-xs"></i> {{ __('lang.common.back') }}
        </a>
        @endcan
    </div>
</div>

<form action="javascript:void(0);" method="POST" id="form" novalidate>
    @csrf
    @method('PUT')

    {{-- Single Form Card --}}
    <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
        {{-- Form Fields --}}
        <div class="p-5">
            <div class="grid grid-cols-12 gap-4">
                {{-- Key --}}
                <div class="col-span-12 sm:col-span-6">
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5" for="key">
                        {{ __('lang.labels.env_variable.key') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="key" name="key" value="{{ old('key', $envVariable->key) }}" required
                           placeholder="APP_DEBUG, DB_HOST, etc." pattern="^[A-Z][A-Z0-9_]*$"
                           title="{{ __('validation.env_variable.key_format') }}"
                           class="w-full h-9 rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500">
                    <small class="text-zinc-400 text-xs mt-1">{{ __('validation.env_variable.key_format') }}</small>
                    <div class="mt-1 text-sm text-red-500" id="error_key"></div>
                </div>

                {{-- Type --}}
                <div class="col-span-12 sm:col-span-6">
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5" for="type">
                        {{ __('lang.labels.env_variable.type') }} <span class="text-red-500">*</span>
                    </label>
                    <select id="type" name="type" required
                            class="w-full h-9 rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700">
                        <option value="">{{ __('lang.common.select') }}</option>
                        <option value="text" {{ old('type', $envVariable->type) == 'text' ? 'selected' : '' }}>{{ __('lang.labels.env_variable.type_text') }}</option>
                        <option value="number" {{ old('type', $envVariable->type) == 'number' ? 'selected' : '' }}>{{ __('lang.labels.env_variable.type_number') }}</option>
                        <option value="boolean" {{ old('type', $envVariable->type) == 'boolean' ? 'selected' : '' }}>{{ __('lang.labels.env_variable.type_boolean') }}</option>
                        <option value="select" {{ old('type', $envVariable->type) == 'select' ? 'selected' : '' }}>{{ __('lang.labels.env_variable.type_select') }}</option>
                        <option value="password" {{ old('type', $envVariable->type) == 'password' ? 'selected' : '' }}>{{ __('lang.labels.env_variable.type_password') }}</option>
                    </select>
                    <div class="mt-1 text-sm text-red-500" id="error_type"></div>
                </div>

                {{-- Category --}}
                <div class="col-span-12 sm:col-span-6">
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5" for="category">{{ __('lang.labels.env_variable.category') }}</label>
                    <input type="text" id="category" name="category" value="{{ old('category', $envVariable->category) }}"
                           placeholder="Application, Database, Mail, etc."
                           class="w-full h-9 rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500">
                    <div class="mt-1 text-sm text-red-500" id="error_category"></div>
                </div>

                {{-- Sort Order --}}
                <div class="col-span-12 sm:col-span-6">
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5" for="sort_order">{{ __('lang.labels.env_variable.sort_order') }}</label>
                    <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $envVariable->sort_order) }}" min="0"
                           placeholder="0, 10, 20, etc."
                           class="w-full h-9 rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500">
                    <div class="mt-1 text-sm text-red-500" id="error_sort_order"></div>
                </div>

                {{-- Status Toggle --}}
                <div class="col-span-12 sm:col-span-6 flex items-end">
                    <div class="flex items-center gap-3">
                        <label class="text-sm font-medium text-zinc-700">{{ __('lang.labels.env_variable.status') }}</label>
                        <div class="erp-toggle relative cursor-pointer" style="width:36px;height:20px;background:{{ old('is_active', $envVariable->is_active) ? 'var(--erp-primary)' : 'var(--erp-border)' }};border-radius:9999px;transition:background-color 0.2s;"
                             onclick="var cb=this.querySelector('input');cb.checked=!cb.checked;this.style.backgroundColor=cb.checked?'var(--erp-primary)':'var(--erp-border)';this.querySelector('.erp-toggle-dot').style.transform=cb.checked?'translateX(16px)':'translateX(0)';">
                            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $envVariable->is_active) ? 'checked' : '' }} class="absolute" style="opacity:0;width:0;height:0;">
                            <div class="erp-toggle-dot absolute bg-white rounded-full shadow" style="width:16px;height:16px;top:2px;left:2px;transition:transform 0.2s;transform:{{ old('is_active', $envVariable->is_active) ? 'translateX(16px)' : 'translateX(0)' }};"></div>
                        </div>
                        <span class="text-sm text-zinc-500">{{ __('lang.labels.env_variable.is_active') }}</span>
                    </div>
                </div>

                {{-- Value --}}
                <div class="col-span-12">
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5" for="value">{{ __('lang.labels.env_variable.value') }}</label>
                    <div id="value_field_container">
                        @if($envVariable->is_encrypted)
                            <div class="flex items-center gap-2">
                                <textarea id="value" name="value" rows="3"
                                          placeholder="{{ __('envvariable::message.enter_new_encrypted_value') }}"
                                          class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500">{{ old('value') }}</textarea>
                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium whitespace-nowrap" style="background-color: var(--erp-warning-bg); color: var(--erp-warning-text);">
                                    <i class="fa-solid fa-lock mr-1"></i> {{ __('lang.labels.env_variable.encrypted_value') }}
                                </span>
                            </div>
                            <small class="text-zinc-400 text-xs mt-1">{{ __('envvariable::message.encrypted_value_edit_note') }}</small>
                        @else
                            <textarea id="value" name="value" rows="3"
                                      placeholder="{{ __('lang.labels.env_variable.value') }}"
                                      class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500">{{ old('value', $envVariable->decrypted_value ?? $envVariable->value) }}</textarea>
                        @endif
                    </div>
                    <div class="mt-1 text-sm text-red-500" id="error_value"></div>
                </div>

                {{-- Options (for select type) --}}
                <div class="col-span-12" id="options_field" style="display: none;">
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5" for="options">{{ __('lang.labels.env_variable.options') }}</label>
                    <textarea id="options" name="options" rows="3"
                              placeholder='["option1", "option2", "option3"]'
                              class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500">{{ old('options') ? json_encode(old('options')) : ($envVariable->options ? json_encode($envVariable->options) : '') }}</textarea>
                    <small class="text-zinc-400 text-xs mt-1">{{ __('envvariable::message.options_help') }}</small>
                    <div class="mt-1 text-sm text-red-500" id="error_options"></div>
                </div>

                {{-- Validation Rules --}}
                <div class="col-span-12">
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5" for="validation_rules">{{ __('lang.labels.env_variable.validation_rules') }}</label>
                    <input type="text" id="validation_rules" name="validation_rules" value="{{ old('validation_rules', $envVariable->validation_rules) }}"
                           placeholder="required|numeric|min:0"
                           class="w-full h-9 rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500">
                    <small class="text-zinc-400 text-xs mt-1">{{ __('envvariable::message.validation_rules_help') }}</small>
                    <div class="mt-1 text-sm text-red-500" id="error_validation_rules"></div>
                </div>

                {{-- Description --}}
                <div class="col-span-12">
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5" for="description">{{ __('lang.labels.env_variable.description') }}</label>
                    <textarea id="description" name="description" rows="2"
                              placeholder="{{ __('lang.labels.env_variable.description') }}"
                              class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500">{{ old('description', $envVariable->description) }}</textarea>
                    <div class="mt-1 text-sm text-red-500" id="error_description"></div>
                </div>
            </div>
        </div>

        {{-- Configuration Options --}}
        <div class="p-5 border-t border-zinc-200">
            <h6 class="text-sm font-semibold text-zinc-700 mb-3">Configuration Options</h6>
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                    <div class="flex items-center gap-3">
                        <div class="erp-toggle relative cursor-pointer" style="width:36px;height:20px;background:{{ old('is_encrypted', $envVariable->is_encrypted) ? 'var(--erp-primary)' : 'var(--erp-border)' }};border-radius:9999px;transition:background-color 0.2s;"
                             onclick="var cb=this.querySelector('input');cb.checked=!cb.checked;this.style.backgroundColor=cb.checked?'var(--erp-primary)':'var(--erp-border)';this.querySelector('.erp-toggle-dot').style.transform=cb.checked?'translateX(16px)':'translateX(0)';">
                            <input type="checkbox" name="is_encrypted" id="is_encrypted" value="1" {{ old('is_encrypted', $envVariable->is_encrypted) ? 'checked' : '' }} class="absolute" style="opacity:0;width:0;height:0;">
                            <div class="erp-toggle-dot absolute bg-white rounded-full shadow" style="width:16px;height:16px;top:2px;left:2px;transition:transform 0.2s;transform:{{ old('is_encrypted', $envVariable->is_encrypted) ? 'translateX(16px)' : 'translateX(0)' }};"></div>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-zinc-700"><i class="fa-solid fa-lock text-xs mr-1"></i> {{ __('lang.labels.env_variable.is_encrypted') }}</span>
                            <p class="text-xs text-zinc-400">{{ __('envvariable::message.encryption_change_note') }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                    <div class="flex items-center gap-3">
                        <div class="erp-toggle relative cursor-pointer" style="width:36px;height:20px;background:{{ old('is_sensitive', $envVariable->is_sensitive) ? 'var(--erp-primary)' : 'var(--erp-border)' }};border-radius:9999px;transition:background-color 0.2s;"
                             onclick="var cb=this.querySelector('input');cb.checked=!cb.checked;this.style.backgroundColor=cb.checked?'var(--erp-primary)':'var(--erp-border)';this.querySelector('.erp-toggle-dot').style.transform=cb.checked?'translateX(16px)':'translateX(0)';">
                            <input type="checkbox" name="is_sensitive" id="is_sensitive" value="1" {{ old('is_sensitive', $envVariable->is_sensitive) ? 'checked' : '' }} class="absolute" style="opacity:0;width:0;height:0;">
                            <div class="erp-toggle-dot absolute bg-white rounded-full shadow" style="width:16px;height:16px;top:2px;left:2px;transition:transform 0.2s;transform:{{ old('is_sensitive', $envVariable->is_sensitive) ? 'translateX(16px)' : 'translateX(0)' }};"></div>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-zinc-700"><i class="fa-solid fa-eye-slash text-xs mr-1"></i> {{ __('lang.labels.env_variable.is_sensitive') }}</span>
                            <p class="text-xs text-zinc-400">{{ __('envvariable::message.sensitive_help') }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                    <div class="flex items-center gap-3">
                        <div class="erp-toggle relative cursor-pointer" style="width:36px;height:20px;background:{{ old('is_editable', $envVariable->is_editable) ? 'var(--erp-primary)' : 'var(--erp-border)' }};border-radius:9999px;transition:background-color 0.2s;"
                             onclick="var cb=this.querySelector('input');cb.checked=!cb.checked;this.style.backgroundColor=cb.checked?'var(--erp-primary)':'var(--erp-border)';this.querySelector('.erp-toggle-dot').style.transform=cb.checked?'translateX(16px)':'translateX(0)';">
                            <input type="checkbox" name="is_editable" id="is_editable" value="1" {{ old('is_editable', $envVariable->is_editable) ? 'checked' : '' }} class="absolute" style="opacity:0;width:0;height:0;">
                            <div class="erp-toggle-dot absolute bg-white rounded-full shadow" style="width:16px;height:16px;top:2px;left:2px;transition:transform 0.2s;transform:{{ old('is_editable', $envVariable->is_editable) ? 'translateX(16px)' : 'translateX(0)' }};"></div>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-zinc-700"><i class="fa-solid fa-pen text-xs mr-1"></i> {{ __('lang.labels.env_variable.is_editable') }}</span>
                            <p class="text-xs text-zinc-400">{{ __('envvariable::message.editable_help') }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                    <div class="flex items-center gap-3">
                        <div class="erp-toggle relative cursor-pointer" style="width:36px;height:20px;background:{{ old('requires_restart', $envVariable->requires_restart) ? 'var(--erp-primary)' : 'var(--erp-border)' }};border-radius:9999px;transition:background-color 0.2s;"
                             onclick="var cb=this.querySelector('input');cb.checked=!cb.checked;this.style.backgroundColor=cb.checked?'var(--erp-primary)':'var(--erp-border)';this.querySelector('.erp-toggle-dot').style.transform=cb.checked?'translateX(16px)':'translateX(0)';">
                            <input type="checkbox" name="requires_restart" id="requires_restart" value="1" {{ old('requires_restart', $envVariable->requires_restart) ? 'checked' : '' }} class="absolute" style="opacity:0;width:0;height:0;">
                            <div class="erp-toggle-dot absolute bg-white rounded-full shadow" style="width:16px;height:16px;top:2px;left:2px;transition:transform 0.2s;transform:{{ old('requires_restart', $envVariable->requires_restart) ? 'translateX(16px)' : 'translateX(0)' }};"></div>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-zinc-700"><i class="fa-solid fa-rotate text-xs mr-1"></i> {{ __('lang.labels.env_variable.requires_restart') }}</span>
                            <p class="text-xs text-zinc-400">{{ __('envvariable::message.restart_help') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sync Env File --}}
        <div class="p-5 border-t border-zinc-200">
            <div class="flex items-center gap-3">
                <div class="erp-toggle relative cursor-pointer" style="width:36px;height:20px;background:var(--erp-border);border-radius:9999px;transition:background-color 0.2s;"
                     onclick="var cb=this.querySelector('input');cb.checked=!cb.checked;this.style.backgroundColor=cb.checked?'var(--erp-primary)':'var(--erp-border)';this.querySelector('.erp-toggle-dot').style.transform=cb.checked?'translateX(16px)':'translateX(0)';">
                    <input type="checkbox" name="sync_env_file" id="sync_env_file" value="1" {{ old('sync_env_file') ? 'checked' : '' }} class="absolute" style="opacity:0;width:0;height:0;">
                    <div class="erp-toggle-dot absolute bg-white rounded-full shadow" style="width:16px;height:16px;top:2px;left:2px;transition:transform 0.2s;transform:translateX(0);"></div>
                </div>
                <div>
                    <span class="text-sm font-medium text-zinc-700"><i class="fa-solid fa-rotate text-xs mr-1"></i> {{ __('lang.labels.env_variable.sync_env_file') }}</span>
                    <p class="text-xs text-zinc-400">{{ __('envvariable::message.sync_help') }}</p>
                </div>
            </div>
        </div>

        {{-- Remarks Field --}}
        <div class="p-5 border-t border-zinc-200">
            @include('partials-tw.remarks-field', ['type' => 'update'])
        </div>

        {{-- Action Buttons --}}
        <div class="p-5 border-t border-zinc-200 flex items-center justify-between">
            <a href="{{ route('env-variable.index') }}"
               class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center gap-2">
                <i class="fa-solid fa-xmark text-xs"></i> {{ __('lang.common.cancel') }}
            </a>
            @can('env-variable-edit')
            <button type="button" id="save" data-route="{{ route('env-variable.update', $envVariable->public_id ?: $envVariable->id) }}"
                    class="h-9 px-4 rounded-md text-sm font-medium whitespace-nowrap inline-flex items-center gap-2 update"
                    style="background-color: var(--erp-primary); color: var(--erp-primary-fg);">
                <i class="fa-solid fa-check text-xs"></i> {{ __('lang.common.update') }}
            </button>
            @endcan
        </div>
    </div>
    </div>
</form>
@endsection

@section('pagescript')
<script>
$(document).ready(function() {
    var isEncrypted = @json($envVariable->is_encrypted);

    // Render value field based on type
    function renderValueField(type, value, options) {
        // Don't change encrypted fields
        if (isEncrypted) return;

        var container = $('#value_field_container');
        var inputClass = 'w-full h-9 rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500';
        var textareaClass = 'w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500';
        var html = '';
        value = value || '';
        options = options || [];

        switch (type) {
            case 'boolean':
                var isChecked = value === '1' || value === 'true';
                html = '<div class="flex items-center gap-3 mt-2">' +
                    '<div class="erp-toggle relative cursor-pointer" style="width:36px;height:20px;background:' + (isChecked ? 'var(--erp-primary)' : 'var(--erp-border)') + ';border-radius:9999px;transition:background-color 0.2s;" onclick="var cb=this.querySelector(\'input\');cb.checked=!cb.checked;this.style.backgroundColor=cb.checked?\'var(--erp-primary)\':\'var(--erp-border)\';this.querySelector(\'.erp-toggle-dot\').style.transform=cb.checked?\'translateX(16px)\':\'translateX(0)\';">' +
                    '<input type="hidden" name="value" value="0">' +
                    '<input type="checkbox" name="value" id="value" value="1" ' + (isChecked ? 'checked' : '') + ' class="absolute" style="opacity:0;width:0;height:0;">' +
                    '<div class="erp-toggle-dot absolute bg-white rounded-full shadow" style="width:16px;height:16px;top:2px;left:2px;transition:transform 0.2s;transform:' + (isChecked ? 'translateX(16px)' : 'translateX(0)') + ';"></div>' +
                    '</div><span class="text-sm text-zinc-700">{{ __('lang.labels.env_variable.enable') }}</span></div>';
                break;
            case 'select':
                html = '<select name="value" id="value" class="' + inputClass + '"><option value="">{{ __('lang.common.select') }}</option>';
                if (options.length > 0) {
                    options.forEach(function(opt) {
                        html += '<option value="' + opt + '" ' + (opt === value ? 'selected' : '') + '>' + opt + '</option>';
                    });
                }
                html += '</select>';
                break;
            case 'number':
                html = '<input type="number" name="value" id="value" class="' + inputClass + '" placeholder="{{ __('lang.labels.env_variable.value') }}" value="' + value + '">';
                break;
            case 'password':
                html = '<input type="password" name="value" id="value" class="' + inputClass + '" placeholder="{{ __('lang.labels.env_variable.value') }}" value="' + value + '">';
                break;
            default:
                html = '<textarea name="value" id="value" rows="3" class="' + textareaClass + '" placeholder="{{ __('lang.labels.env_variable.value') }}">' + value + '</textarea>';
        }
        container.html(html);
    }

    $('#type').on('change', function() {
        var selectedType = $(this).val();
        var currentValue = $('#value').val() || '';
        if (selectedType === 'select') {
            $('#options_field').show();
            var optionsText = $('#options').val();
            var opts = [];
            try { opts = optionsText ? JSON.parse(optionsText) : []; } catch(e) { opts = []; }
            renderValueField(selectedType, currentValue, opts);
        } else {
            $('#options_field').hide();
            renderValueField(selectedType, currentValue);
        }
    });

    $('#options').on('input', function() {
        if ($('#type').val() === 'select') {
            var currentValue = $('#value').val() || '';
            var opts = [];
            try { opts = $(this).val() ? JSON.parse($(this).val()) : []; } catch(e) { opts = []; }
            renderValueField('select', currentValue, opts);
        }
    });

    // Initialize form based on current values
    var currentType = $('#type').val();
    var currentValue = @json(old('value', $envVariable->decrypted_value ?? $envVariable->value));
    var currentOptions = @json($envVariable->options);

    if (currentType === 'select') {
        $('#options_field').show();
        renderValueField(currentType, currentValue, currentOptions || []);
    } else if (currentType) {
        renderValueField(currentType, currentValue);
    }

    // Form submission handled by global update.js (.update class + data-route)
});
</script>
@endsection
