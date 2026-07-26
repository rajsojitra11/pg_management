@php
    $activityIcons = [
        'login' => ['icon' => 'fa-right-to-bracket', 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-100'],
        'login_failed' => ['icon' => 'fa-triangle-exclamation', 'color' => 'text-red-600', 'bg' => 'bg-red-100'],
        'logout' => ['icon' => 'fa-right-from-bracket', 'color' => 'text-zinc-600', 'bg' => 'bg-zinc-100'],
        'created' => ['icon' => 'fa-plus', 'color' => 'text-rose-600', 'bg' => 'bg-rose-100'],
        'updated' => ['icon' => 'fa-pen', 'color' => 'text-amber-600', 'bg' => 'bg-amber-100'],
        'deleted' => ['icon' => 'fa-trash-can', 'color' => 'text-red-600', 'bg' => 'bg-red-100'],
        'restored' => ['icon' => 'fa-rotate-left', 'color' => 'text-blue-600', 'bg' => 'bg-blue-100'],
        'password_changed' => ['icon' => 'fa-key', 'color' => 'text-purple-600', 'bg' => 'bg-purple-100'],
        'blocked' => ['icon' => 'fa-ban', 'color' => 'text-red-600', 'bg' => 'bg-red-100'],
        'unblocked' => ['icon' => 'fa-unlock', 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-100'],
        'activated' => ['icon' => 'fa-toggle-on', 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-100'],
        'deactivated' => ['icon' => 'fa-toggle-off', 'color' => 'text-zinc-600', 'bg' => 'bg-zinc-100'],
        'force_deleted' => ['icon' => 'fa-trash', 'color' => 'text-red-600', 'bg' => 'bg-red-100'],
        'viewed' => ['icon' => 'fa-eye', 'color' => 'text-blue-600', 'bg' => 'bg-blue-100'],
        'exported' => ['icon' => 'fa-file-export', 'color' => 'text-cyan-600', 'bg' => 'bg-cyan-100'],
        'imported' => ['icon' => 'fa-file-import', 'color' => 'text-violet-600', 'bg' => 'bg-violet-100'],
        'bulk_operation' => ['icon' => 'fa-layer-group', 'color' => 'text-orange-600', 'bg' => 'bg-orange-100'],
    ];
    $style = $activityIcons[$log->activity] ?? ['icon' => 'fa-circle', 'color' => 'text-blue-600', 'bg' => 'bg-blue-100'];
@endphp
<div class="relative flex gap-4 pb-6 group">
    {{-- Timeline dot --}}
    <div class="relative flex flex-col items-center shrink-0">
        <div class="h-9 w-9 rounded-full {{ $style['bg'] }} flex items-center justify-center ring-4 ring-white z-10">
            <i class="fa-solid {{ $style['icon'] }} text-xs {{ $style['color'] }}"></i>
        </div>
    </div>
    {{-- Content --}}
    <div class="flex-1 min-w-0 pt-1">
        <p class="text-sm font-medium text-zinc-900 leading-snug">
            {{ $log->system_remark ?? ucfirst(str_replace('_', ' ', $log->activity)) }}
        </p>
        <div class="flex items-center gap-2 mt-1.5 text-xs text-zinc-400">
            <span>
                @php
                    $createdAt = $log->created_at ? \Carbon\Carbon::parse($log->created_at) : null;
                @endphp
                @if($createdAt)
                    {{ $createdAt->format('h:i A') }}
                @endif
            </span>
            @if($log->ip_address)
                <span class="text-zinc-300">·</span>
                <span class="font-mono">{{ $log->ip_address }}</span>
            @endif
            @if($log->browser)
                <span class="text-zinc-300">·</span>
                <span>{{ $log->browser }}</span>
            @endif
            @if($log->platform)
                <span class="text-zinc-300">·</span>
                <span>{{ $log->platform }}</span>
            @endif
            @if($log->device && $log->device !== 'Unknown' && $log->device !== 'WebKit')
                <span class="text-zinc-300">·</span>
                <span>{{ $log->device }}</span>
            @endif
        </div>
    </div>
</div>