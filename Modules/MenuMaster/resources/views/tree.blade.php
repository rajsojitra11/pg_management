@if ($items->count() > 0)
    <ul class="list-none">
        @foreach ($items as $item)
            <li class="mb-2 menu-item drag-handle" style="cursor: move;" title="Drag to reorder"
                data-id="{{ $item->id }}">
                <div class="flex items-center p-2 border rounded" style="background: var(--erp-bg-muted); border-color: var(--erp-border);">
                    <span class="mr-2">
                        <i class="fa-solid fa-grip-vertical" style="color: var(--erp-text-secondary);"></i>
                    </span>

                    @if ($item->menu_icon)
                        <i class="{{ $item->menu_icon }} mr-2" style="color: var(--erp-text-medium);"></i>
                    @endif

                    <div class="flex-1" style="color: var(--erp-text);">
                        <strong>{{ $item->getHumanReadableOrder() }}</strong>
                        {{ __($item->menu_title) }}

                        @if ($item->module_name)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium whitespace-nowrap ml-2" style="background: var(--erp-bg-muted); color: var(--erp-text-secondary); border: 1px solid var(--erp-border);">{{ $item->module_name }}</span>
                        @endif
                        @if ($item->if_can)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium whitespace-nowrap ml-1" style="background: var(--erp-info-bg); color: var(--erp-info-text);" title="Permission: {{ $item->if_can }}">
                                <i class="fa-solid fa-lock text-xs"></i>
                            </span>
                        @endif
                    </div>

                    <div class="flex gap-1 ml-2">
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
                </div>

                @if ($item->children->count() > 0)
                    <div class="ml-4 mt-2">
                        @include('menumaster::tree', ['items' => $item->children])
                    </div>
                @endif
            </li>
        @endforeach
    </ul>
@else
    <p class="text-center text-sm" style="color: var(--erp-text-secondary);">
        {{ __('menumaster::message.noMenuItemsFound') }}
    </p>
@endif
