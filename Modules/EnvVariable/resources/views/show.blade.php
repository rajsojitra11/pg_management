@extends('layouts.app-tw')
@section('title', __('lang.labels.env_variable.view'))
@section('nav-module', 'envvariable')
@section('breadcrumb', 'Home > Env Variables > View')

@section('pagecss')
<style>
    .env-timeline {
        position: relative;
        padding-left: 30px;
    }
    .env-timeline::before {
        content: '';
        position: absolute;
        left: 15px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: var(--erp-border);
    }
    .env-timeline-item {
        position: relative;
    }
    .env-timeline-marker {
        position: absolute;
        left: -23px;
        top: 5px;
        z-index: 2;
    }
    .env-timeline-marker .env-marker-badge {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: bold;
        border-radius: 9999px;
    }
    .env-timeline-content {
        border-radius: 8px;
        padding: 12px;
        border-left: 3px solid var(--erp-border);
        background: var(--erp-bg-muted);
    }
</style>
@endsection

@section('content')
{{-- Page Header --}}
<div class="flex items-center justify-between mb-6">
    <h5 class="text-lg font-semibold text-zinc-900">{{ __('lang.labels.env_variable.view') }}</h5>
    <div class="flex items-center gap-2">
        @can('env-variable-list')
        <a href="{{ route('env-variable.index') }}"
           class="h-10 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center gap-2">
            <i class="fa-solid fa-arrow-left text-xs"></i> {{ __('lang.common.back') }}
        </a>
        @endcan
        @can('env-variable-edit')
        <a href="{{ route('env-variable.edit', $envVariable->public_id ?: $envVariable->id) }}"
           class="h-10 px-4 rounded-full bg-blue-50 text-sm font-medium text-blue-700 hover:bg-blue-100 whitespace-nowrap inline-flex items-center gap-2">
            <i class="fa-solid fa-pen text-xs"></i> {{ __('lang.common.edit') }}
        </a>
        @endcan
    </div>
</div>

<div class="grid grid-cols-12 gap-4">
    {{-- Details Card --}}
    <div class="col-span-12 lg:col-span-8">
        <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
            {{-- Card Header --}}
            <div class="px-4 py-3 border-b border-zinc-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-info text-zinc-400 text-xs"></i>
                    <span class="text-sm font-semibold text-zinc-700">{{ __('lang.labels.env_variable.details') }}</span>
                </div>
                @if($envVariable->is_active)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 whitespace-nowrap">{{ __('lang.active') }}</span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700 whitespace-nowrap">{{ __('lang.inactive') }}</span>
                @endif
            </div>

            {{-- Card Body --}}
            <div class="p-5">
                <div class="grid grid-cols-12 gap-4">
                    {{-- Key --}}
                    <div class="col-span-12">
                        <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('lang.labels.env_variable.key') }}</label>
                        <div class="flex items-center gap-2">
                            <code class="flex-grow px-3 py-2 rounded-md text-sm text-zinc-900 font-mono" style="background: var(--erp-bg-muted);">{{ $envVariable->key }}</code>
                            @if($envVariable->is_encrypted)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 whitespace-nowrap">
                                    <i class="fa-solid fa-lock mr-1"></i> {{ __('lang.labels.env_variable.is_encrypted') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Value --}}
                    <div class="col-span-12">
                        <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('lang.labels.env_variable.value') }}</label>
                        <div class="px-3 py-3 rounded-md" style="background: var(--erp-bg-muted);">
                            @if($envVariable->is_encrypted)
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-lock text-xs" style="color: var(--erp-warning-text);"></i>
                                    <span class="text-sm text-zinc-500">{{ __('lang.labels.env_variable.encrypted_value') }}</span>
                                    <small class="text-zinc-400 text-xs">({{ __('envvariable::message.encrypted_hidden') }})</small>
                                </div>
                            @else
                                <pre class="text-sm text-zinc-900 font-mono whitespace-pre-wrap break-words" style="margin:0;">{{ $envVariable->value ?: __('lang.common.no_value_set') }}</pre>
                            @endif
                        </div>
                    </div>

                    {{-- Description --}}
                    @if($envVariable->description)
                    <div class="col-span-12">
                        <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('lang.labels.env_variable.description') }}</label>
                        <p class="text-sm text-zinc-900">{{ $envVariable->description }}</p>
                    </div>
                    @endif

                    {{-- Status & Encrypted --}}
                    <div class="col-span-12 sm:col-span-6">
                        <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('lang.labels.env_variable.status') }}</label>
                        @if($envVariable->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 whitespace-nowrap">{{ __('lang.active') }}</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700 whitespace-nowrap">{{ __('lang.inactive') }}</span>
                        @endif
                    </div>

                    <div class="col-span-12 sm:col-span-6">
                        <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('lang.labels.env_variable.is_encrypted') }}</label>
                        @if($envVariable->is_encrypted)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 whitespace-nowrap">{{ __('lang.common.yes') }}</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-zinc-100 text-zinc-600 whitespace-nowrap">{{ __('lang.common.no') }}</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Timestamps --}}
            <div class="px-4 py-3 border-t border-zinc-200">
                <div class="text-xs text-zinc-500 space-y-1">
                    <div><span class="font-medium text-zinc-700">{{ __('lang.common.created_at') }}:</span> {{ $envVariable->created_at->format('d M Y, H:i:s') }}</div>
                    <div><span class="font-medium text-zinc-700">{{ __('lang.common.updated_at') }}:</span> {{ $envVariable->updated_at->format('d M Y, H:i:s') }}</div>
                    @if($envVariable->createdBy)
                        <div><span class="font-medium text-zinc-700">{{ __('lang.labels.env_variable.created_by') }}:</span> {{ $envVariable->createdBy->name ?? 'N/A' }}</div>
                    @endif
                    @if($envVariable->updatedBy)
                        <div><span class="font-medium text-zinc-700">{{ __('lang.labels.env_variable.updated_by') }}:</span> {{ $envVariable->updatedBy->name ?? 'N/A' }}</div>
                    @endif
                    @if($envVariable->deletedBy)
                        <div><span class="font-medium text-zinc-700">{{ __('lang.labels.env_variable.deleted_by') }}:</span> {{ $envVariable->deletedBy->name ?? 'N/A' }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Activity Logs Card --}}
    <div class="col-span-12 lg:col-span-4">
        <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
            {{-- Card Header --}}
            <div class="px-4 py-3 border-b border-zinc-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-zinc-400 text-xs"></i>
                    <span class="text-sm font-semibold text-zinc-700">{{ __('lang.labels.env_variable.activity_logs') }}</span>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 whitespace-nowrap">
                    {{ $envVariable->logs->count() }} {{ __('lang.common.entries') }}
                </span>
            </div>

            {{-- Card Body --}}
            <div class="p-4">
                @if($envVariable->logs && $envVariable->logs->count() > 0)
                    <div class="env-timeline">
                        @foreach($envVariable->logs->sortByDesc('created_at') as $log)
                        <div class="env-timeline-item mb-4">
                            <div class="env-timeline-marker">
                                @if($log->activity === 'created')
                                    <span class="env-marker-badge bg-emerald-50 text-emerald-700" title="{{ __('lang.common.created') }}">
                                        <i class="fa-solid fa-plus"></i>
                                    </span>
                                @elseif($log->activity === 'updated')
                                    <span class="env-marker-badge bg-blue-50 text-blue-700" title="{{ __('lang.common.updated') }}">
                                        <i class="fa-solid fa-pen"></i>
                                    </span>
                                @elseif($log->activity === 'deleted')
                                    <span class="env-marker-badge bg-red-50 text-red-700" title="{{ __('lang.common.deleted') }}">
                                        <i class="fa-solid fa-trash"></i>
                                    </span>
                                @else
                                    <span class="env-marker-badge bg-zinc-100 text-zinc-600">
                                        <i class="fa-solid fa-info"></i>
                                    </span>
                                @endif
                            </div>
                            <div class="env-timeline-content">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h6 class="text-sm font-medium text-zinc-900 capitalize">{{ $log->activity }}</h6>
                                        <p class="text-xs text-zinc-400 mt-0.5">
                                            <i class="fa-regular fa-clock mr-0.5"></i> {{ $log->created_at->format('d M Y, H:i:s') }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs text-zinc-500">
                                            <i class="fa-solid fa-user mr-0.5"></i> {{ $log->user?->name ?? 'System' }}
                                        </span>
                                        @if($log->ip_address)
                                            <br><span class="text-xs text-zinc-400">
                                                <i class="fa-solid fa-globe mr-0.5"></i> {{ $log->ip_address }}
                                            </span>
                                        @endif
                                    </div>
                                </div>


                                @if($log->system_remark)
                                <div class="mb-2">
                                    <span class="text-xs font-medium text-zinc-500">{{ __('lang.labels.env_variable.system_remark') }}:</span>
                                    <p class="text-xs text-zinc-400 mt-0.5">{{ $log->system_remark }}</p>
                                </div>
                                @endif

                                @if($log->old_values || $log->new_values)
                                <details class="mt-2">
                                    <summary class="text-xs font-medium cursor-pointer select-none" style="color: var(--erp-primary);">
                                        {{ __('lang.labels.env_variable.view_changes') }}
                                    </summary>
                                    <div class="mt-2 space-y-2">
                                        @if($log->old_values)
                                        <div>
                                            <span class="text-xs font-medium text-red-600">{{ __('lang.labels.env_variable.old_values') }}:</span>
                                            <pre class="text-xs mt-0.5 px-2 py-1.5 rounded overflow-x-auto" style="background: var(--erp-bg-page); margin: 0;">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                        @endif
                                        @if($log->new_values)
                                        <div>
                                            <span class="text-xs font-medium text-emerald-600">{{ __('lang.labels.env_variable.new_values') }}:</span>
                                            <pre class="text-xs mt-0.5 px-2 py-1.5 rounded overflow-x-auto" style="background: var(--erp-bg-page); margin: 0;">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                        @endif
                                    </div>
                                </details>
                                @endif

                                @if($log->device || $log->browser || $log->platform)
                                <div class="mt-2 pt-2 border-t" style="border-color: var(--erp-border);">
                                    <span class="text-xs text-zinc-400">
                                        @if($log->device)
                                            <i class="fa-solid fa-desktop mr-0.5"></i> {{ $log->device }}
                                        @endif
                                        @if($log->browser)
                                            @if($log->device) | @endif
                                            <i class="fa-solid fa-globe mr-0.5"></i> {{ $log->browser }}
                                        @endif
                                        @if($log->platform)
                                            @if($log->device || $log->browser) | @endif
                                            <i class="fa-solid fa-laptop mr-0.5"></i> {{ $log->platform }}
                                        @endif
                                    </span>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fa-solid fa-clock-rotate-left text-4xl text-zinc-300"></i>
                        <p class="text-sm text-zinc-400 mt-3">{{ __('lang.common.no_activity_logs') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
