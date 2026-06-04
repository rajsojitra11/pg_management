{{-- Permission grid partial — used by Role create/edit and User edit --}}
{{-- Expects: $permission (grouped array), $rolePermissions (array of checked IDs, optional) --}}
{{-- Optional: $userMode (bool) — when true, shows role-inherited as locked, direct as toggleable --}}
{{-- Optional: $directPermissionIds (array) — IDs of directly assigned permissions (user mode only) --}}
@php
    $rolePermissions = $rolePermissions ?? [];
    $userMode = $userMode ?? false;
    $directPermissionIds = $directPermissionIds ?? [];
    $inputName = $userMode ? 'direct_permissions[]' : 'permission[]';
@endphp

{{-- Header bar --}}
<div class="flex items-center justify-between mb-4">
    <div>
        @if ($userMode)
            <h6 class="text-base font-semibold text-zinc-900">{{ __('user::message.direct_permissions') }}</h6>
            <p class="text-xs mt-0.5" style="color: var(--erp-text-secondary);">
                <span class="inline-flex items-center rounded px-1.5 py-0.5 text-xs mr-1" style="background-color: var(--erp-bg-muted); color: var(--erp-text-secondary);">
                    <i class="fa-solid fa-lock mr-1" style="font-size:8px;"></i>From Role
                </span>
                = inherited from role (cannot be changed here)
            </p>
        @else
            <h6 class="text-base font-semibold text-zinc-900">{{ __('role::message.role_permissions') }}</h6>
            <p class="text-xs mt-0.5" style="color: var(--erp-text-secondary);">Configure module-level access for this role</p>
        @endif
    </div>
    @if (!$userMode)
    <label class="flex items-center gap-2.5 cursor-pointer select-none h-9 px-4 rounded-md border transition-colors erp-select-all-label"
           style="border-color: var(--erp-border);">
        <input type="checkbox" id="selectAll" class="erp-perm-checkbox">
        <span class="text-sm font-medium" style="color: var(--erp-text);">{{ __('role::message.select_all') }}</span>
    </label>
    @endif
</div>

@foreach ($permission as $sectionName => $permissionGroups)
    {{-- Section card --}}
    <div class="rounded-lg border mb-4 overflow-hidden" style="border-color: var(--erp-border);">
        {{-- Section header --}}
        <div class="flex items-center gap-2 px-4 py-2.5" style="background: linear-gradient(135deg, rgba(var(--erp-primary-glow-rgb, 61,82,160), 0.08), rgba(var(--erp-primary-glow-rgb, 61,82,160), 0.03));">
            <div class="h-6 w-6 rounded flex items-center justify-center" style="background-color: var(--erp-primary); color: var(--erp-primary-fg);">
                <i class="fa-solid fa-shield-halved" style="font-size: 10px;"></i>
            </div>
            <span class="text-sm font-bold" style="color: var(--erp-primary);">{{ $sectionName }}</span>
            <span class="text-xs ml-auto rounded-full px-2 py-0.5" style="background-color: var(--erp-bg-muted); color: var(--erp-text-secondary);">
                {{ count($permissionGroups) }} {{ count($permissionGroups) === 1 ? 'group' : 'groups' }}
            </span>
        </div>

        {{-- Permission rows --}}
        <div class="divide-y" style="divide-color: var(--erp-border);">
            @foreach ($permissionGroups as $titleTag => $permissionGroup)
                <div class="flex items-center px-4 py-2.5 gap-4 hover:bg-zinc-50 transition-colors group">
                    {{-- Module name --}}
                    <div class="shrink-0" style="min-width: 140px;">
                        <span class="text-sm font-medium" style="color: var(--erp-text);">
                            {{ str_replace('_', ' ', $permissionGroup['name']) }}
                        </span>
                    </div>

                    {{-- All toggle (role mode only) --}}
                    @if (!$userMode)
                    <label class="flex items-center gap-1.5 cursor-pointer shrink-0 rounded px-2 py-1 transition-colors hover:bg-zinc-100"
                           style="min-width: 52px;">
                        <input type="checkbox" class="s-child parent-checkbox erp-perm-checkbox"
                               id="all_{{ $permissionGroup['name'] }}"
                               data-child="child_{{ $permissionGroup['name'] }}">
                        <span class="text-xs font-bold uppercase" style="color: var(--erp-text-tertiary);">All</span>
                    </label>
                    @endif

                    {{-- Permission chips --}}
                    <div class="flex flex-wrap gap-1.5 flex-1">
                        @foreach ($permissionGroup['child'] as $child)
                            @php
                                $isFromRole = $userMode && in_array($child->id, $rolePermissions);
                                $isDirectlyAssigned = $userMode && in_array($child->id, $directPermissionIds);
                                $isChecked = $userMode
                                    ? ($isFromRole || $isDirectlyAssigned)
                                    : in_array($child->id, $rolePermissions);
                            @endphp

                            @if ($isFromRole)
                                {{-- Role-inherited: emerald tone + locked --}}
                                <span class="erp-perm-chip erp-perm-locked inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1 text-xs font-semibold select-none"
                                      style="background-color: #ecfdf5; color: #065f46; border-color: #a7f3d0; cursor: not-allowed;"
                                      title="Inherited from role — cannot be changed here">
                                    <i class="fa-solid fa-lock" style="font-size:8px;"></i>
                                    <span>{{ $child->title ?: ucfirst(last(explode('-', $child->name))) }}</span>
                                </span>
                            @else
                                <label class="erp-perm-chip inline-flex items-center gap-1.5 cursor-pointer rounded-md border px-2.5 py-1 text-xs transition-all select-none"
                                       style="border-color: var(--erp-border); color: var(--erp-text-secondary);">
                                    <input type="checkbox" name="{{ $inputName }}" value="{{ $child->id }}"
                                           class="s-child child-checkbox child_{{ $permissionGroup['name'] }} erp-perm-checkbox"
                                           id="child_{{ $child->id }}"
                                           data-parent="{{ $permissionGroup['name'] }}"
                                           {{ $isChecked ? 'checked' : '' }}
                                           style="display:none;">
                                    <i class="fa-solid fa-check text-white" style="font-size:8px; display:none;"></i>
                                    <span>{{ $child->title ?: ucfirst(last(explode('-', $child->name))) }}</span>
                                </label>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endforeach

{{-- Permission chip styling --}}
<style>
    .erp-perm-checkbox {
        width: 16px; height: 16px;
        accent-color: var(--erp-primary);
        cursor: pointer;
    }
    .erp-perm-chip {
        user-select: none;
    }
    .erp-perm-chip.checked {
        background-color: var(--erp-primary);
        color: var(--erp-primary-fg) !important;
        border-color: var(--erp-primary) !important;
        font-weight: 600;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .erp-perm-chip.checked i {
        display: inline-block !important;
    }
    .erp-perm-chip:not(.checked):not(.erp-perm-locked):hover {
        border-color: var(--erp-primary) !important;
        color: var(--erp-primary) !important;
    }
    .erp-select-all-label.checked {
        background-color: var(--erp-primary) !important;
        border-color: var(--erp-primary) !important;
        color: var(--erp-primary-fg) !important;
    }
    .erp-select-all-label.checked span {
        color: var(--erp-primary-fg) !important;
    }
</style>

{{-- Chip JS — waits for jQuery then initializes --}}
<script>
// Global function — called by create/edit page handlers after programmatic checkbox changes
function syncChipState() {
    $('.erp-perm-chip').each(function() {
        var $input = $(this).find('input');
        if ($input.length) {
            $(this).toggleClass('checked', $input.is(':checked'));
        }
    });
    $('.erp-select-all-label').each(function() {
        $(this).toggleClass('checked', $(this).find('input').is(':checked'));
    });
}
function _initPermChips() {
    // Click handler — ensures chip toggle works reliably
    $(document).on('click', '.erp-perm-chip:not(.erp-perm-locked)', function(e) {
        e.preventDefault();
        var $input = $(this).find('input[type="checkbox"]');
        if ($input.length) {
            $input.prop('checked', !$input.prop('checked')).trigger('change');
        }
    });
    // Sync on any chip/checkbox native change
    $(document).on('change', '.erp-perm-chip input, .erp-select-all-label input', syncChipState);
    // Initial sync for pre-checked (edit mode)
    syncChipState();
}
// Wait for jQuery — script may load before jQuery in the DOM
(function _waitJQ() {
    if (typeof $ !== 'undefined') { $(document).ready(_initPermChips); }
    else { setTimeout(_waitJQ, 50); }
})();
</script>
