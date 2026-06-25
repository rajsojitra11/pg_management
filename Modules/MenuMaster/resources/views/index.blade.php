@extends('layouts.app-tw')
@section('title', __('menumaster::message.menumaster'))

@section('nav-module', 'menumaster')
@section('breadcrumb', 'Home > Menu Master')

@section('content')
<div class="grid grid-cols-12 gap-4">
    {{-- Page Header --}}
    <div class="col-span-12 flex flex-wrap items-center justify-between gap-2">
        <h4 class="text-base font-semibold" style="color: var(--erp-text);">{{ __('menumaster::message.menumaster') }}</h4>
        <div class="flex gap-2">
            @can('menu-master-create')
            <a href="{{ route('menumasters.create') }}" class="h-8 px-3 rounded-md text-sm font-medium inline-flex items-center" style="background: var(--erp-primary); color: var(--erp-primary-fg);">
                <i class="fa-solid fa-plus mr-1.5 text-xs"></i> {{ __('menumaster::message.menuManagementAddItem') }}
            </a>
            @endcan
            <div class="relative" id="toolsDropdown">
                <button type="button" class="h-8 px-3 rounded-md text-sm font-medium inline-flex items-center border" style="background: var(--erp-bg); color: var(--erp-text); border-color: var(--erp-border);" onclick="$('#toolsMenu').toggle()">
                    <i class="fa-solid fa-wrench mr-1.5 text-xs"></i> {{ __('menumaster::message.menutools') }}
                    <i class="fa-solid fa-chevron-down ml-1.5 text-xs"></i>
                </button>
                <div id="toolsMenu" class="absolute right-0 mt-1 w-56 rounded-lg shadow-lg py-1 z-50" style="display:none; background: var(--erp-bg); border: 1px solid var(--erp-border);">
                    <a href="javascript:void(0)" onclick="normalizeOrders(); $('#toolsMenu').hide();" class="flex items-center gap-2 px-3 py-2 text-sm transition-colors" style="color: var(--erp-text);">
                        <i class="fa-solid fa-sort-numeric-down w-4 text-xs" style="color: var(--erp-text-secondary);"></i>
                        {{ __('menumaster::message.menuToolsNormalizeOrders') }}
                    </a>
                    <a href="javascript:void(0)" onclick="rebuildHierarchy(); $('#toolsMenu').hide();" class="flex items-center gap-2 px-3 py-2 text-sm transition-colors" style="color: var(--erp-text);">
                        <i class="fa-solid fa-sitemap w-4 text-xs" style="color: var(--erp-text-secondary);"></i>
                        {{ __('menumaster::message.menuToolsRebuildHierarchy') }}
                    </a>
                    <div class="my-1" style="border-top: 1px solid var(--erp-border);"></div>
                    @can('menu-master-export')
                    <a href="{{ route('menumasters.export') }}" class="flex items-center gap-2 px-3 py-2 text-sm transition-colors" style="color: var(--erp-text);">
                        <i class="fa-solid fa-download w-4 text-xs" style="color: var(--erp-text-secondary);"></i>
                        {{ __('menumaster::message.menuToolsExportStructure') }}
                    </a>
                    @endcan
                    <a href="javascript:void(0)" onclick="showStatistics(); $('#toolsMenu').hide();" class="flex items-center gap-2 px-3 py-2 text-sm transition-colors" style="color: var(--erp-text);">
                        <i class="fa-solid fa-chart-bar w-4 text-xs" style="color: var(--erp-text-secondary);"></i>
                        {{ __('menumaster::message.menuToolsShowStatistics') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="col-span-12">
        <div class="rounded-lg border p-4" style="background: var(--erp-bg); border-color: var(--erp-border);">
            {{-- Module Filter --}}
            <div class="grid grid-cols-12 gap-4 mb-4">
                <div class="col-span-12 sm:col-span-6 md:col-span-4">
                    <label for="moduleFilter" class="block text-sm font-medium mb-1" style="color: var(--erp-text-medium);">{{ __('menumaster::message.menuFilterByModule') }}</label>
                    <select id="moduleFilter" class="w-full rounded-md px-3 py-2 text-sm" style="background: var(--erp-input-bg); border: 1px solid var(--erp-border); color: var(--erp-text);" onchange="filterByModule()">
                        <option value="">{{ __('menumaster::message.menuAllModules') }}</option>
                        @foreach ($modules as $module => $menuTitle)
                            @php
                                $moduleName2 = $module ?? '';
                                $menuTitle = $menuTitle ?? 'general';
                            @endphp
                            <option value="{{ $module }}" {{ ($moduleName ?? '') == $module ? 'selected' : '' }}>
                                {{ ucfirst(__($menuTitle)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Menu Structure --}}
            <div class="grid grid-cols-12 gap-4">
                {{-- Tree Structure --}}
                <div class="col-span-12 lg:col-span-5">
                    <div class="rounded-lg border p-3 h-full" style="border-color: var(--erp-border);">
                        <h5 class="text-sm font-semibold mb-3" style="color: var(--erp-text);">{{ __('menumaster::message.treestructure') }}</h5>
                        <div id="menu-tree" style="overflow-y: auto; max-height: 600px;">
                            @include('menumaster::tree', ['items' => $menuTree])
                        </div>
                    </div>
                </div>

                {{-- Flattened List --}}
                <div class="col-span-12 lg:col-span-7">
                    <div class="overflow-x-auto">
                        <h5 class="text-sm font-semibold mb-3" style="color: var(--erp-text);">{{ __('menumaster::message.menuFlattenedList') }}</h5>
                        <table class="w-full text-sm border" style="border-color: var(--erp-border);">
                            <thead>
                                <tr style="background: var(--erp-primary); color: var(--erp-primary-fg);">
                                    <th class="px-3 py-2 text-left text-xs font-medium" style="width: 15%;">{{ __('menumaster::message.menuSequence') }}</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium">{{ __('menumaster::message.menutitle') }}</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium" style="width: 15%;">{{ __('menumaster::message.menumodulename') }}</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium" style="width: 15%;">{{ __('menumaster::message.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($menuItems as $item)
                                    <tr class="border-b" style="border-color: var(--erp-border);">
                                        <td class="px-3 py-2">
                                            <small style="color: var(--erp-text-secondary);">{{ $item->getHumanReadableOrder() }}</small>
                                        </td>
                                        <td class="px-3 py-2">
                                            <div class="flex items-center" style="color: var(--erp-text);">
                                                {!! str_repeat('&nbsp;&nbsp;&nbsp;', $item->getLevel() - 1) !!}
                                                @if ($item->menu_icon)
                                                    <i class="{{ $item->menu_icon }} mr-2" style="color: var(--erp-text-medium);"></i>
                                                @endif
                                                <span>{{ __($item->menu_title) }}</span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2">
                                            @if ($item->module_name)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium whitespace-nowrap" style="background: var(--erp-bg-muted); color: var(--erp-text-secondary); border: 1px solid var(--erp-border);">{{ $item->module_name }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">
                                            <div class="flex gap-1">
                                                @can('menu-master-list')
                                                <a href="{{ route('menumasters.show', $item) }}" class="py-1 px-1.5 rounded-md bg-emerald-50 text-emerald-700 text-xs font-medium inline-flex items-center" title="{{ __('menumaster::message.view') }}">
                                                    <i class="fa-solid fa-eye" style="font-size:10px;"></i>
                                                </a>
                                                @endcan
                                                @can('menu-master-edit')
                                                <a href="{{ route('menumasters.edit', $item) }}" class="py-1 px-1.5 rounded-md bg-blue-50 text-blue-700 text-xs font-medium inline-flex items-center" title="{{ __('menumaster::message.edit') }}">
                                                    <i class="fa-solid fa-pen" style="font-size:10px;"></i>
                                                </a>
                                                @endcan
                                                @can('menu-master-create')
                                                <button type="button" class="py-1 px-1.5 rounded-md bg-zinc-100 text-zinc-700 text-xs font-medium inline-flex items-center duplicate-item" data-id="{{ $item->id }}" title="{{ __('menumaster::message.duplicate') }}">
                                                    <i class="fa-solid fa-copy" style="font-size:10px;"></i>
                                                </button>
                                                @endcan
                                                @can('menu-master-delete')
                                                <button type="button" class="py-1 px-1.5 rounded-md bg-red-50 text-red-700 text-xs font-medium inline-flex items-center delete-item" data-id="{{ $item->id }}" title="{{ __('menumaster::message.delete') }}">
                                                    <i class="fa-solid fa-trash" style="font-size:10px;"></i>
                                                </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-4 text-center text-sm" style="color: var(--erp-text-secondary);">
                                            {{ __('menumaster::message.noMenuItemsFoundModule') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Statistics Modal --}}
<div id="statisticsModal" class="erp-inline-modal" style="display:none;">
    <div class="erp-inline-modal-backdrop" onclick="$('#statisticsModal').hide()"></div>
    <div class="erp-inline-modal-card w-full max-w-md">
        <div class="flex items-center justify-between p-4 border-b" style="border-color: var(--erp-border);">
            <h6 class="text-sm font-semibold" style="color: var(--erp-text);">{{ __('menumaster::message.menuStatistics') }}</h6>
            <button type="button" class="text-zinc-400 cursor-pointer" onclick="$('#statisticsModal').hide()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-4" id="statisticsContent">
            <div class="text-center py-4" style="color: var(--erp-text-secondary);">Loading...</div>
        </div>
        <div class="flex justify-end p-4 border-t" style="border-color: var(--erp-border);">
            <button type="button" class="erp-modal-btn-secondary" onclick="$('#statisticsModal').hide()">
                <i class="fa-solid fa-xmark mr-1.5 text-xs"></i> {{ __('menumaster::message.close') }}
            </button>
        </div>
    </div>
</div>
@endsection

@section('pagecss')
<style>
    .sortable-ghost {
        opacity: 0.4;
        border: 2px dashed var(--erp-primary) !important;
    }
    .sortable-chosen {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        transform: rotate(2deg);
    }
    .menu-item {
        transition: all 0.2s ease;
        cursor: default;
    }
    .drag-handle {
        cursor: grab;
        padding: 5px;
        border-radius: 3px;
    }
    .drag-handle:active {
        cursor: grabbing;
    }
    .menu-item .menu-item {
        margin-left: 10px;
        border-left: 2px solid var(--erp-border);
        padding-left: 10px;
    }
    ul.list-none {
        min-height: 20px;
    }
    .sortable-chosen * {
        user-select: none;
    }
    .menu-item.sortable-chosen {
        z-index: 1000;
        position: relative;
    }
</style>
@endsection

@section('pagescript')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    $(document).ready(function() {
        // Close tools dropdown when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#toolsDropdown').length) {
                $('#toolsMenu').hide();
            }
        });

        // Initialize sortable for all menu levels
        initializeSortable();

        // Delete item
        $(document).on('click', '.delete-item', function() {
            var itemId = $(this).data('id');
            erpConfirm({
                title: '{{ __('menumaster::message.delete') }}',
                message: '{{ __('menumaster::message.confirmDeleteMenuItem') }}',
                confirmText: '{{ __('menumaster::message.delete') }}',
                type: 'destructive',
            }).then(function (confirmed) {
                if (!confirmed) return;
                deleteMenuItem(itemId);
            });
        });

        // Duplicate item
        $(document).on('click', '.duplicate-item', function() {
            var itemId = $(this).data('id');
            duplicateMenuItem(itemId);
        });
    });

    function initializeSortable() {
        var sortableContainers = document.querySelectorAll('#menu-tree ul.list-none');
        sortableContainers.forEach(function(container) {
            if (container.children.length > 0) {
                Sortable.create(container, {
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    group: 'menu-items',
                    fallbackOnBody: true,
                    swapThreshold: 0.65,
                    onEnd: function(evt) {
                        var itemId = evt.item.dataset.id;
                        var newIndex = evt.newIndex;
                        var parentElement = evt.to.closest('.menu-item');
                        if (evt.to.closest('#menu-tree') && !parentElement) {
                            parentElement = null;
                        }
                        var parentId = parentElement ? parentElement.dataset.id : null;
                        moveMenuItem(itemId, parentId, newIndex);
                    }
                });
            }
        });
    }

    function filterByModule() {
        var module = document.getElementById('moduleFilter').value;
        var url = new URL(window.location);
        if (module) {
            url.searchParams.set('module_name', module);
        } else {
            url.searchParams.delete('module_name');
        }
        window.location.href = url.toString();
    }

    function moveMenuItem(itemId, parentId, position) {
        $.post('menumasters/' + itemId + '/move', {
            parent_id: parentId,
            position: position,
            _token: $('meta[name="csrf-token"]').attr('content')
        })
        .done(function(response) {
            if (response.success) {
                erpToast({ title: 'Success', message: response.message, type: 'success' });
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                erpToast({ title: 'Error', message: response.message || '{{ __('menumaster::message.errorMovingMenuItem') }}', type: 'error' });
                location.reload();
            }
        })
        .fail(function(xhr) {
            erpToast({ title: 'Error', message: '{{ __('menumaster::message.errorMovingMenuItem') }}', type: 'error' });
            location.reload();
        });
    }

    function deleteMenuItem(itemId) {
        $.ajax({
            url: 'menumasters/' + itemId,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
        .done(function(response) {
            if (response.success) {
                erpToast({ title: 'Success', message: response.message, type: 'success' });
                setTimeout(function() { location.reload(); }, 1000);
            }
        })
        .fail(function() {
            erpToast({ title: 'Error', message: '{{ __('menumaster::message.errorDeletingMenuItem') }}', type: 'error' });
        });
    }

    function duplicateMenuItem(itemId) {
        $.post('menumasters/' + itemId + '/duplicate', {
            _token: $('meta[name="csrf-token"]').attr('content')
        })
        .done(function(response) {
            if (response.success) {
                erpToast({ title: 'Success', message: response.message, type: 'success' });
                setTimeout(function() { location.reload(); }, 1000);
            }
        })
        .fail(function() {
            erpToast({ title: 'Error', message: '{{ __('menumaster::message.error_duplicating') }}', type: 'error' });
        });
    }

    function normalizeOrders() {
        erpConfirm({
            title: '{{ __('menumaster::message.menuToolsNormalizeOrders') }}',
            message: '{{ __('menumaster::message.confirmNormalizeOrders') }}',
            confirmText: '{{ __('menumaster::message.menuToolsNormalizeOrders') }}',
        }).then(function (confirmed) {
            if (!confirmed) return;
            $.post('menumasters/normalize-orders', {
                _token: $('meta[name="csrf-token"]').attr('content')
            })
            .done(function(response) {
                if (response.success) {
                    erpToast({ title: 'Success', message: response.message, type: 'success' });
                    setTimeout(function() { location.reload(); }, 1000);
                }
            })
            .fail(function() {
                erpToast({ title: 'Error', message: '{{ __('menumaster::message.errorNormalizingOrders') }}', type: 'error' });
            });
        });
    }

    function rebuildHierarchy() {
        erpConfirm({
            title: '{{ __('menumaster::message.menuToolsRebuildHierarchy') }}',
            message: '{{ __('menumaster::message.confirmRebuildHierarchy') }}',
            confirmText: '{{ __('menumaster::message.menuToolsRebuildHierarchy') }}',
            type: 'destructive',
        }).then(function (confirmed) {
            if (!confirmed) return;
            $.post('menumasters/rebuild-hierarchy', {
                _token: $('meta[name="csrf-token"]').attr('content')
            })
            .done(function(response) {
                if (response.success) {
                    erpToast({ title: 'Success', message: response.message, type: 'success' });
                    setTimeout(function() { location.reload(); }, 1000);
                }
            })
            .fail(function() {
                erpToast({ title: 'Error', message: '{{ __('menumaster::message.errorRebuildingHierarchy') }}', type: 'error' });
            });
        });
    }

    function showStatistics() {
        $('#statisticsModal').show();
        $.get('menumasters/statistics')
        .done(function(response) {
            if (response.success) {
                var stats = response.data;
                var content = '<div class="space-y-2 text-sm">';
                content += '<div class="flex justify-between py-1 border-b" style="border-color: var(--erp-border);"><span class="font-medium" style="color: var(--erp-text-medium);">{{ __('menumaster::message.totalMenus') }}:</span><span style="color: var(--erp-text);">' + stats.total_menus + '</span></div>';
                content += '<div class="flex justify-between py-1 border-b" style="border-color: var(--erp-border);"><span class="font-medium" style="color: var(--erp-text-medium);">{{ __('menumaster::message.mainMenus') }}:</span><span style="color: var(--erp-text);">' + stats.main_menus + '</span></div>';
                content += '<div class="flex justify-between py-1 border-b" style="border-color: var(--erp-border);"><span class="font-medium" style="color: var(--erp-text-medium);">{{ __('menumaster::message.subMenus') }}:</span><span style="color: var(--erp-text);">' + stats.sub_menus + '</span></div>';
                content += '<div class="flex justify-between py-1 border-b" style="border-color: var(--erp-border);"><span class="font-medium" style="color: var(--erp-text-medium);">{{ __('menumaster::message.withRoutes') }}:</span><span style="color: var(--erp-text);">' + stats.menus_with_routes + '</span></div>';
                content += '<div class="flex justify-between py-1 border-b" style="border-color: var(--erp-border);"><span class="font-medium" style="color: var(--erp-text-medium);">{{ __('menumaster::message.withPermissions') }}:</span><span style="color: var(--erp-text);">' + stats.menus_with_permissions + '</span></div>';
                content += '<div class="flex justify-between py-1 border-b" style="border-color: var(--erp-border);"><span class="font-medium" style="color: var(--erp-text-medium);">{{ __('menumaster::message.modules') }}:</span><span style="color: var(--erp-text);">' + stats.modules + '</span></div>';
                content += '<div class="flex justify-between py-1"><span class="font-medium" style="color: var(--erp-text-medium);">{{ __('menumaster::message.maxDepth') }}:</span><span style="color: var(--erp-text);">' + stats.max_depth + '</span></div>';
                content += '</div>';
                document.getElementById('statisticsContent').innerHTML = content;
            }
        })
        .fail(function() {
            erpToast({ title: 'Error', message: '{{ __('menumaster::message.errorLoadingStatistics') }}', type: 'error' });
        });
    }
</script>
@endsection
