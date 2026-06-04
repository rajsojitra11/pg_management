@extends('layouts.app-tw')

@section('title', 'Dashboard Widget Settings')

@section('content')
<div class="mb-6">
    <h5 class="text-lg font-semibold" style="color: var(--erp-text);">Dashboard Widget Settings</h5>
    <p class="text-sm mt-1" style="color: var(--erp-text-secondary);">Configure which widgets each role sees on the dashboard.</p>
</div>

{{-- Role selector tabs --}}
<div class="flex flex-wrap gap-2 mb-6">
    @foreach ($roles as $i => $role)
        <button class="erp-role-tab h-9 px-4 rounded-md text-sm font-medium whitespace-nowrap inline-flex items-center transition-colors {{ $i === 0 ? 'bg-zinc-900 text-white' : 'border border-zinc-200 text-zinc-600 hover:bg-zinc-100' }}"
                data-role-id="{{ $role->id }}" data-role-name="{{ $role->name }}">
            {{ str_replace('_', ' ', $role->name) }}
        </button>
    @endforeach
</div>

{{-- Widget grid per role --}}
@foreach ($roles as $i => $role)
    <div class="erp-role-panel rounded-lg border shadow-sm p-5 mb-4 {{ $i === 0 ? '' : 'hidden' }}"
         data-role-panel="{{ $role->id }}"
         style="background-color: var(--erp-bg); border-color: var(--erp-border);">

        <div class="flex items-center justify-between mb-4">
            <h6 class="text-base font-semibold" style="color: var(--erp-text);">
                {{ str_replace('_', ' ', $role->name) }} — Widgets
            </h6>
            <button class="erp-save-role-btn h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 whitespace-nowrap inline-flex items-center"
                    data-role-id="{{ $role->id }}">
                <i class="fa-solid fa-check mr-1.5 text-xs"></i> Save
            </button>
        </div>

        @foreach ($sections as $sectionKey => $sectionLabel)
            @php
                $sectionWidgets = $widgets->where('section', $sectionKey);
            @endphp
            @if ($sectionWidgets->count() > 0)
                <div class="mb-4">
                    <p class="text-xs font-semibold uppercase tracking-wider mb-2" style="color: var(--erp-text-secondary);">{{ $sectionLabel }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach ($sectionWidgets as $widget)
                            @php
                                $configKey = $role->id . '_' . $widget->id;
                                $config = $roleConfigs->get($configKey);
                                $enabled = $config ? $config->enabled : $widget->default_enabled;
                            @endphp
                            <label class="flex items-center gap-3 p-3 rounded-md border cursor-pointer transition-colors hover:bg-zinc-50"
                                   style="border-color: var(--erp-border);">
                                <input type="checkbox" name="widgets[{{ $widget->id }}]"
                                       class="erp-widget-toggle"
                                       style="width:18px;height:18px;accent-color:var(--erp-primary);border-radius:4px;cursor:pointer;"
                                       data-widget-id="{{ $widget->id }}"
                                       {{ $enabled ? 'checked' : '' }}>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        @if ($widget->icon)
                                            <i class="{{ $widget->icon }} text-xs" style="color: var(--erp-text-secondary);"></i>
                                        @endif
                                        <span class="text-sm font-medium truncate" style="color: var(--erp-text);">{{ $widget->title }}</span>
                                    </div>
                                    @if ($widget->permission)
                                        <span class="text-xs" style="color: var(--erp-text-tertiary);">Requires: {{ $widget->permission }}</span>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endforeach
@endsection

@section('pagescript')
<script>
$(document).ready(function() {
    // Tab switching
    $(document).on('click', '.erp-role-tab', function() {
        var roleId = $(this).data('role-id');
        $('.erp-role-tab').removeClass('bg-zinc-900 text-white').addClass('border border-zinc-200 text-zinc-600 hover:bg-zinc-100');
        $(this).removeClass('border border-zinc-200 text-zinc-600 hover:bg-zinc-100').addClass('bg-zinc-900 text-white');
        $('.erp-role-panel').addClass('hidden');
        $('[data-role-panel="' + roleId + '"]').removeClass('hidden');
    });

    // Save role config
    $(document).on('click', '.erp-save-role-btn', function() {
        var btn = $(this);
        var roleId = btn.data('role-id');
        var panel = $('[data-role-panel="' + roleId + '"]');
        var widgets = {};

        panel.find('.erp-widget-toggle').each(function() {
            widgets[$(this).data('widget-id')] = $(this).is(':checked') ? 1 : 0;
        });

        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin mr-1.5 text-xs"></i> Saving...');

        $.ajax({
            url: '{{ route("dashboard.config.role") }}',
            type: 'POST',
            data: { role_id: roleId, widgets: widgets, _token: $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                erpToast({ title: 'Success', message: 'Configuration saved for role.', type: 'success' });
            },
            error: function() {
                erpToast({ title: 'Error', message: 'Failed to save configuration.', type: 'error' });
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fa-solid fa-check mr-1.5 text-xs"></i> Save');
            }
        });
    });
});
</script>
@endsection
