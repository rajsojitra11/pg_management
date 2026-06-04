{{-- Action Buttons Partial — Tailwind Theme
     Used in DataTable action columns (server-side rendered)
     All buttons use padding-based sizing (no fixed h-*) for dynamic content --}}

{{-- View --}}
@can($show)
    @if ($showURL != '')
        <a href="{{ $showURL }}" class="p-1.5 rounded-md text-blue-500 hover:text-blue-700 hover:bg-blue-50 inline-flex items-center" title="View">
            <i class="fa-solid fa-eye text-xs"></i>
        </a>
    @else
        <button data-id="{{ $row->public_id ?? $row->id }}" class="p-1.5 rounded-md text-blue-500 hover:text-blue-700 hover:bg-blue-50 inline-flex items-center view" title="View">
            <i class="fa-solid fa-eye text-xs"></i>
        </button>
    @endif
@endcan

{{-- Edit --}}
@can($edit)
    @if ($editURL != '')
        <a href="{{ $editURL }}" class="p-1.5 rounded-md text-amber-500 hover:text-amber-700 hover:bg-amber-50 inline-flex items-center" title="Edit">
            <i class="fa-solid fa-pen text-xs"></i>
        </a>
    @else
        <button data-id="{{ $row->public_id ?? $row->id }}" class="p-1.5 rounded-md text-amber-500 hover:text-amber-700 hover:bg-amber-50 inline-flex items-center edit" title="Edit">
            <i class="fa-solid fa-pen text-xs"></i>
        </button>
    @endif
@endcan

{{-- Print --}}
@if (!empty($printURL))
    <a href="{{ $printURL }}" target="_blank" class="p-1.5 rounded-md text-sky-500 hover:text-sky-700 hover:bg-sky-50 inline-flex items-center" title="Print">
        <i class="fa-solid fa-print text-xs"></i>
    </a>
@elseif (!empty($print))
    <button type="button" onclick="if(typeof erpToast==='function')erpToast({type:'info',title:'Print',message:'{{ $print }}'});" class="p-1.5 rounded-md text-sky-500 hover:text-sky-700 hover:bg-sky-50 inline-flex items-center" title="Print">
        <i class="fa-solid fa-print text-xs"></i>
    </button>
@endif

{{-- Delete --}}
{{-- $deleteURL (optional): override the DELETE base path (delete.js appends /{id}).
     Used for item-level deletes that target a different endpoint than window.URL_ROUTE.
     Empty string falls back to window.URL_ROUTE inside delete.js. --}}
@can($delete)
    <button data-id="{{ $row->public_id ?? $row->id }}" data-url="{{ $deleteURL ?? '' }}" class="p-1.5 rounded-md text-red-400 hover:text-red-600 hover:bg-red-50 inline-flex items-center delete" title="Delete">
        <i class="fa-solid fa-trash text-xs"></i>
    </button>
@endcan

{{-- Receive --}}
@isset($receive)
    @can($receive)
        @if ($receiveURL != '')
            <a href="{{ $receiveURL }}" class="py-1.5 px-2.5 rounded-md bg-blue-50 text-blue-700 text-xs font-medium hover:bg-blue-100 whitespace-nowrap inline-flex items-center" title="Receive">
                <i class="fa-solid fa-truck mr-1 text-[10px]"></i>Receive
            </a>
        @else
            <button data-id="{{ $row->public_id ?? $row->id }}" class="py-1.5 px-2.5 rounded-md bg-blue-50 text-blue-700 text-xs font-medium hover:bg-blue-100 whitespace-nowrap inline-flex items-center receive" title="Receive">
                <i class="fa-solid fa-truck mr-1 text-[10px]"></i>Receive
            </button>
        @endif
    @endcan
@endisset

{{-- Assign User --}}
@isset($assign)
    @can($assign)
        <button data-id="{{ $row->user_id }}" class="py-1.5 px-2.5 rounded-md bg-purple-50 text-purple-700 text-xs font-medium hover:bg-purple-100 whitespace-nowrap inline-flex items-center assignUser" title="Assign User">
            <i class="fa-solid fa-user-plus mr-1 text-[10px]"></i>Assign
        </button>
    @endcan
@endisset

{{-- Clone --}}
@isset($clone)
    @can($clone)
        <button data-id="{{ $row->public_id ?? $row->id }}" class="py-1.5 px-2.5 rounded-md bg-zinc-100 text-zinc-700 text-xs font-medium hover:bg-zinc-200 whitespace-nowrap inline-flex items-center clone" title="Clone">
            <i class="fa-solid fa-copy mr-1 text-[10px]"></i>Clone
        </button>
    @endcan
@endisset

{{-- Order Log --}}
@if (!empty($orderLog))
    <button data-id="{{ $row->public_id ?? $row->id }}" class="py-1.5 px-2.5 rounded-md bg-zinc-100 text-zinc-700 text-xs font-medium hover:bg-zinc-200 whitespace-nowrap inline-flex items-center {{ $orderLog }}" title="Status Log">
        <i class="fa-solid fa-clock-rotate-left mr-1 text-[10px]"></i>Log
    </button>
@endif

{{-- PDF --}}
@if (!empty($pdf))
    @isset($pdfPermission)
        @can($pdfPermission)
            <a href="{{ $pdf }}" target="_blank" class="p-1.5 rounded-md text-rose-500 hover:text-rose-700 hover:bg-rose-50" title="Export PDF">
                <i class="fa-solid fa-file-pdf text-xs"></i>
            </a>
        @endcan
    @else
       <a href="{{ $pdf }}" target="_blank" class="p-1.5 rounded-md text-rose-500 hover:text-rose-700 hover:bg-rose-50" title="Export PDF">
            <i class="fa-solid fa-file-pdf text-xs"></i>
        </a>
    @endisset
@endif

{{-- Extra Buttons --}}
@if (isset($extraBtn) && count($extraBtn) > 0)
    @foreach ($extraBtn as $extra)
        @can($extra['extra'])
            @php
                $colorMap = [
                    'primary' => 'bg-blue-50 text-blue-700 hover:bg-blue-100',
                    'success' => 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100',
                    'danger' => 'bg-red-50 text-red-700 hover:bg-red-100',
                    'warning' => 'bg-amber-50 text-amber-700 hover:bg-amber-100',
                    'info' => 'bg-blue-50 text-blue-700 hover:bg-blue-100',
                    'secondary' => 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200',
                ];
                $btnColor = $colorMap[$extra['extraBgColor'] ?? 'secondary'] ?? $colorMap['secondary'];
            @endphp
            @if ($extra['extraURL'] != '')
                <a href="{{ $extra['extraURL'] ?? '#' }}"
                   class="py-1.5 px-2.5 rounded-md {{ $btnColor }} text-xs font-medium whitespace-nowrap inline-flex items-center"
                   title="{{ $extra['extraToolTip'] ?? '' }}">
                    <i class="fa-solid fa-{{ $extra['extraIcon'] ?? 'circle' }} mr-1 text-[10px]"></i>{{ $extra['extraToolTip'] ?? '' }}
                </a>
            @else
                <button data-id="{{ $row->public_id ?? $row->id }}"
                        class="py-1.5 px-2.5 rounded-md {{ $btnColor }} text-xs font-medium whitespace-nowrap inline-flex items-center {{ $extra['extraClass'] ?? '' }}"
                        title="{{ $extra['extraToolTip'] ?? '' }}">
                    <i class="fa-solid fa-{{ $extra['extraIcon'] ?? 'circle' }} mr-1 text-[10px]"></i>{{ $extra['extraToolTip'] ?? '' }}
                </button>
            @endif
        @endcan
    @endforeach
@endif
