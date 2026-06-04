@extends('layouts.app-tw')
@section('title', __('menumaster::message.details'))
@section('nav-module', 'menumaster')
@section('breadcrumb', 'Home > Menu Master > Details')

@section('content')
{{-- Page Header --}}
<div class="flex flex-wrap items-center justify-between gap-2 mb-6">
    <h5 class="text-lg font-semibold text-zinc-900 flex items-center gap-2">
        @if ($menuMaster->menu_icon)
            <i class="{{ $menuMaster->menu_icon }}"></i>
        @endif
        {{ __($menuMaster->menu_title) }}
        @if ($menuMaster->module_name)
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium whitespace-nowrap bg-zinc-100 text-zinc-500 border border-zinc-200">{{ $menuMaster->module_name }}</span>
        @endif
    </h5>
    <div class="flex gap-2">
        <a href="{{ route('menumasters.index') }}"
           class="h-10 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center gap-2">
            <i class="fa-solid fa-arrow-left text-xs"></i> {{ __('message.common.back') }}
        </a>
        @can('menu-master-edit')
        <a href="{{ route('menumasters.edit', $menuMaster) }}"
           class="h-10 px-4 rounded-md text-sm font-medium whitespace-nowrap inline-flex items-center gap-2"
           style="background-color: var(--erp-primary); color: var(--erp-primary-fg);">
            <i class="fa-solid fa-pen text-xs"></i> {{ __('menumaster::message.edit') }}
        </a>
        @endcan
    </div>
</div>

<div class="grid grid-cols-12 gap-5">
    {{-- Basic Information --}}
    <div class="col-span-12 md:col-span-6">
        <div class="rounded-lg border border-zinc-200 bg-white shadow-sm h-full">
            <div class="px-4 py-3 border-b border-zinc-200 flex items-center gap-2">
                <i class="fa-solid fa-info-circle text-zinc-400 text-xs"></i>
                <h6 class="text-sm font-semibold text-zinc-700">{{ __('menumaster::message.basic_information') }}</h6>
            </div>
            <div class="p-5">
                <table class="w-full text-sm">
                    <tr class="border-b border-zinc-100">
                        <th class="py-2.5 pr-3 text-left font-medium text-zinc-500" style="width: 30%;">{{ __('menumaster::message.id') }}:</th>
                        <td class="py-2.5 text-zinc-900">{{ $menuMaster->id }}</td>
                    </tr>
                    <tr class="border-b border-zinc-100">
                        <th class="py-2.5 pr-3 text-left font-medium text-zinc-500">{{ __('menumaster::message.title') }}:</th>
                        <td class="py-2.5 text-zinc-900">{{ __($menuMaster->menu_title) }}</td>
                    </tr>
                    <tr class="border-b border-zinc-100">
                        <th class="py-2.5 pr-3 text-left font-medium text-zinc-500">{{ __('menumaster::message.icon') }}:</th>
                        <td class="py-2.5 text-zinc-900">
                            @if ($menuMaster->menu_icon)
                                <i class="{{ $menuMaster->menu_icon }} mr-2"></i>
                                <code class="px-1.5 py-0.5 rounded text-xs bg-zinc-100 text-zinc-600">{{ $menuMaster->menu_icon }}</code>
                            @else
                                <span class="text-zinc-400">{{ __('menumaster::message.no_icon') }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="border-b border-zinc-100">
                        <th class="py-2.5 pr-3 text-left font-medium text-zinc-500">{{ __('menumaster::message.route_url') }}</th>
                        <td class="py-2.5">
                            @if ($menuMaster->menu_route)
                                <a href="{{ $menuMaster->getFullRouteAttribute() }}" target="_blank" class="no-underline" style="color: var(--erp-primary);">
                                    {{ $menuMaster->menu_route }}
                                    <i class="fa-solid fa-external-link-alt ml-1 text-xs"></i>
                                </a>
                            @else
                                <span class="text-zinc-400">{{ __('menumaster::message.no_route') }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="py-2.5 pr-3 text-left font-medium text-zinc-500">{{ __('menumaster::message.permission') }}:</th>
                        <td class="py-2.5">
                            @if ($menuMaster->if_can)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium whitespace-nowrap bg-amber-50 text-amber-700 border border-amber-200">
                                    <i class="fa-solid fa-lock mr-1 text-xs"></i>{{ $menuMaster->if_can }}
                                </span>
                            @else
                                <span class="text-zinc-400">{{ __('menumaster::message.no_permission') }}</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Hierarchy Information --}}
    <div class="col-span-12 md:col-span-6">
        <div class="rounded-lg border border-zinc-200 bg-white shadow-sm h-full">
            <div class="px-4 py-3 border-b border-zinc-200 flex items-center gap-2">
                <i class="fa-solid fa-sitemap text-zinc-400 text-xs"></i>
                <h6 class="text-sm font-semibold text-zinc-700">{{ __('menumaster::message.hierarchy_information') }}</h6>
            </div>
            <div class="p-5">
                <table class="w-full text-sm">
                    <tr class="border-b border-zinc-100">
                        <th class="py-2.5 pr-3 text-left font-medium text-zinc-500" style="width: 30%;">{{ __('menumaster::message.order') }}:</th>
                        <td class="py-2.5 font-semibold text-zinc-900">{{ $menuMaster->getHumanReadableOrder() }}</td>
                    </tr>
                    <tr class="border-b border-zinc-100">
                        <th class="py-2.5 pr-3 text-left font-medium text-zinc-500">{{ __('menumaster::message.level') }}:</th>
                        <td class="py-2.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium whitespace-nowrap" style="background-color: var(--erp-primary); color: var(--erp-primary-fg);">{{ __('menumaster::message.level') }} {{ $menuMaster->getLevel() }}</span>
                        </td>
                    </tr>
                    <tr class="border-b border-zinc-100">
                        <th class="py-2.5 pr-3 text-left font-medium text-zinc-500">{{ __('menumaster::message.parent_menu') }}:</th>
                        <td class="py-2.5">
                            @if ($menuMaster->parent)
                                <a href="{{ route('menumasters.show', $menuMaster->parent) }}" class="no-underline" style="color: var(--erp-primary);">
                                    @if ($menuMaster->parent->menu_icon)
                                        <i class="{{ $menuMaster->parent->menu_icon }} mr-1"></i>
                                    @endif
                                    {{ __($menuMaster->parent->menu_title) }}
                                </a>
                            @else
                                <span class="text-zinc-400">{{ __('menumaster::message.no_parent') }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="py-2.5 pr-3 text-left font-medium text-zinc-500">{{ __('menumaster::message.has_children') }}:</th>
                        <td class="py-2.5">
                            @if ($menuMaster->hasChildren())
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium whitespace-nowrap bg-blue-50 text-blue-600 border border-blue-200">{{ $menuMaster->children->count() }} {{ __('menumaster::message.children') }}</span>
                            @else
                                <span class="text-zinc-400">{{ __('menumaster::message.no_children') }}</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Children Menus --}}
    @if ($menuMaster->children && $menuMaster->children->count() > 0)
    <div class="col-span-12">
        <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
            <div class="px-4 py-3 border-b border-zinc-200 flex items-center gap-2">
                <i class="fa-solid fa-layer-group text-zinc-400 text-xs"></i>
                <h6 class="text-sm font-semibold text-zinc-700">{{ __('menumaster::message.child_menus') }} ({{ $menuMaster->children->count() }})</h6>
            </div>
            <div class="p-5 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200">
                            <th class="py-2 text-left font-medium text-zinc-500">#</th>
                            <th class="py-2 text-left font-medium text-zinc-500">{{ __('menumaster::message.title') }}</th>
                            <th class="py-2 text-left font-medium text-zinc-500">{{ __('menumaster::message.route') }}</th>
                            <th class="py-2 text-left font-medium text-zinc-500">{{ __('menumaster::message.permission') }}</th>
                            <th class="py-2 text-left font-medium text-zinc-500">{{ __('menumaster::message.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($menuMaster->children->sortBy('order_display') as $child)
                            <tr class="border-b border-zinc-100">
                                <td class="py-2 text-zinc-700">{{ $loop->iteration }}</td>
                                <td class="py-2 text-zinc-900">
                                    @if ($child->menu_icon)
                                        <i class="{{ $child->menu_icon }} mr-2 text-zinc-400"></i>
                                    @endif
                                    {{ __($child->menu_title) }}
                                </td>
                                <td class="py-2">
                                    @if ($child->menu_route)
                                        <code class="px-1.5 py-0.5 rounded text-xs bg-zinc-100 text-zinc-600">{{ $child->menu_route }}</code>
                                    @else
                                        <span class="text-zinc-400">-</span>
                                    @endif
                                </td>
                                <td class="py-2">
                                    @if ($child->if_can)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium whitespace-nowrap bg-amber-50 text-amber-700 border border-amber-200">{{ $child->if_can }}</span>
                                    @else
                                        <span class="text-zinc-400">-</span>
                                    @endif
                                </td>
                                <td class="py-2">
                                    <div class="flex gap-1">
                                        <a href="{{ route('menumasters.show', $child) }}" class="py-1.5 px-2.5 rounded-md bg-emerald-50 text-emerald-700 text-xs font-medium whitespace-nowrap inline-flex items-center" title="View">
                                            <i class="fa-solid fa-eye mr-1" style="font-size:10px;"></i>View
                                        </a>
                                        <a href="{{ route('menumasters.edit', $child) }}" class="py-1.5 px-2.5 rounded-md bg-blue-50 text-blue-700 text-xs font-medium whitespace-nowrap inline-flex items-center" title="Edit">
                                            <i class="fa-solid fa-pen mr-1" style="font-size:10px;"></i>Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Navigation Preview --}}
    <div class="col-span-12">
        <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
            <div class="px-4 py-3 border-b border-zinc-200 flex items-center gap-2">
                <i class="fa-solid fa-eye text-zinc-400 text-xs"></i>
                <h6 class="text-sm font-semibold text-zinc-700">{{ __('menumaster::message.navigation_preview') }}</h6>
            </div>
            <div class="p-5">
                <div class="rounded-md border border-zinc-200 bg-zinc-50 p-3">
                    <div class="flex items-center mb-2" style="color: var(--erp-primary);">
                        @if ($menuMaster->menu_icon)
                            <i class="{{ $menuMaster->menu_icon }} mr-2 text-lg"></i>
                        @endif
                        <span class="font-semibold text-lg">{{ __($menuMaster->menu_title) }}</span>
                        @if ($menuMaster->hasChildren())
                            <i class="fa-solid fa-chevron-down ml-2 text-zinc-400"></i>
                        @endif
                    </div>
                    @if ($menuMaster->children && $menuMaster->children->count() > 0)
                        <div class="ml-4 mt-2">
                            @foreach ($menuMaster->children->sortBy('order_display')->take(3) as $child)
                                <div class="flex items-center py-1 text-zinc-700">
                                    @if ($child->menu_icon)
                                        <i class="{{ $child->menu_icon }} mr-2 text-zinc-400"></i>
                                    @endif
                                    <span>{{ __($child->menu_title) }}</span>
                                </div>
                            @endforeach
                            @if ($menuMaster->children->count() > 3)
                                <div class="py-1 italic text-sm text-zinc-400">
                                    ... and {{ $menuMaster->children->count() - 3 }} more
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Audit Information --}}
    <div class="col-span-12">
        <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
            <div class="px-4 py-3 border-b border-zinc-200 flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-zinc-400 text-xs"></i>
                <h6 class="text-sm font-semibold text-zinc-700">{{ __('menumaster::message.audit_information') }}</h6>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-12 gap-4 text-sm">
                    <div class="col-span-12 md:col-span-4">
                        <table class="w-full">
                            <tr class="border-b border-zinc-100">
                                <th class="py-2 text-left font-medium text-zinc-500">{{ __('menumaster::message.created_at') }}:</th>
                                <td class="py-2 text-zinc-900">{{ $menuMaster->created_at ? $menuMaster->created_at->format('Y-m-d H:i:s') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="py-2 text-left font-medium text-zinc-500">{{ __('menumaster::message.created_by') }}:</th>
                                <td class="py-2 text-zinc-900">{{ $menuMaster->creator?->name ?? __('menumaster::message.unknown') }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-span-12 md:col-span-4">
                        <table class="w-full">
                            <tr class="border-b border-zinc-100">
                                <th class="py-2 text-left font-medium text-zinc-500">{{ __('menumaster::message.updated_at') }}:</th>
                                <td class="py-2 text-zinc-900">{{ $menuMaster->updated_at ? $menuMaster->updated_at->format('Y-m-d H:i:s') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="py-2 text-left font-medium text-zinc-500">{{ __('menumaster::message.updated_by') }}:</th>
                                <td class="py-2 text-zinc-900">{{ $menuMaster->updater?->name ?? __('menumaster::message.unknown') }}</td>
                            </tr>
                        </table>
                    </div>
                    @if ($menuMaster->deleted_at)
                    <div class="col-span-12 md:col-span-4">
                        <table class="w-full">
                            <tr class="border-b border-zinc-100">
                                <th class="py-2 text-left font-medium text-zinc-500">{{ __('menumaster::message.deleted_at') }}:</th>
                                <td class="py-2 text-zinc-900">{{ $menuMaster->deleted_at->format('Y-m-d H:i:s') }}</td>
                            </tr>
                            <tr>
                                <th class="py-2 text-left font-medium text-zinc-500">{{ __('menumaster::message.deleted_by') }}:</th>
                                <td class="py-2 text-zinc-900">{{ $menuMaster->deleter?->name ?? __('menumaster::message.unknown') }}</td>
                            </tr>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="col-span-12">
        <div class="rounded-lg border border-zinc-200 bg-white shadow-sm p-5 flex flex-wrap items-center justify-between gap-2">
            <a href="{{ route('menumasters.index') }}"
               class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center gap-2">
                <i class="fa-solid fa-arrow-left text-xs"></i> {{ __('message.common.back') }}
            </a>
            <div class="flex gap-2">
                @can('menu-master-create')
                <button type="button" onclick="duplicateMenu()"
                        class="h-10 px-4 rounded-md text-sm font-medium whitespace-nowrap inline-flex items-center gap-2"
                        style="background-color: var(--erp-success-bg-solid); color: white;">
                    <i class="fa-solid fa-copy text-xs"></i> {{ __('menumaster::message.duplicate') }}
                </button>
                @endcan
                @can('menu-master-edit')
                <a href="{{ route('menumasters.edit', $menuMaster) }}"
                   class="h-10 px-4 rounded-md text-sm font-medium whitespace-nowrap inline-flex items-center gap-2"
                   style="background-color: var(--erp-primary); color: var(--erp-primary-fg);">
                    <i class="fa-solid fa-pen text-xs"></i> {{ __('menumaster::message.edit') }}
                </a>
                @endcan
                @can('menu-master-delete')
                <button type="button" onclick="deleteMenu()"
                        class="h-10 px-4 rounded-md text-sm font-medium whitespace-nowrap inline-flex items-center gap-2"
                        style="background-color: var(--erp-danger-bg-solid); color: white;">
                    <i class="fa-solid fa-trash text-xs"></i> {{ __('menumaster::message.delete') }}
                </button>
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection

@section('pagescript')
<script>
function duplicateMenu() {
    erpConfirm({
        title: '{{ __('menumaster::message.duplicate') }}',
        message: '{{ __('menumaster::message.duplicate_confirmation') }}',
        confirmText: '{{ __('menumaster::message.duplicate') }}',
        onConfirm: function() {
            $.post('{{ route('menumasters.duplicate', $menuMaster) }}', {
                _token: '{{ csrf_token() }}'
            })
            .done(function(response) {
                if (response.success) {
                    erpToast({ title: 'Success', message: response.message, type: 'success' });
                    window.location.href = '{{ route('menumasters.index') }}';
                }
            })
            .fail(function() {
                erpToast({ title: 'Error', message: '{{ __('menumaster::message.duplicate_error') }}', type: 'error' });
            });
        }
    });
}

function deleteMenu() {
    var hasChildren = {{ $menuMaster->children->count() }};
    var confirmMessage = hasChildren > 0
        ? '{{ __('menumaster::message.delete_confirmation_with_children', ['count' => $menuMaster->children->count()]) }}'
        : '{{ __('menumaster::message.delete_confirmation') }}';

    erpConfirm({
        title: '{{ __('menumaster::message.delete') }}',
        message: confirmMessage,
        confirmText: '{{ __('menumaster::message.delete') }}',
        type: 'destructive',
        onConfirm: function() {
            $.ajax({
                url: '{{ route('menumasters.destroy', $menuMaster) }}',
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .done(function(response) {
                if (response.success) {
                    erpToast({ title: 'Success', message: response.message, type: 'success' });
                    window.location.href = '{{ route('menumasters.index') }}';
                }
            })
            .fail(function() {
                erpToast({ title: 'Error', message: 'Error deleting menu item', type: 'error' });
            });
        }
    });
}
</script>
@endsection
