@extends('layouts.app-tw')
@section('title', __('lang.labels.env_variable.management'))
@section('nav-module', 'envvariable')
@section('breadcrumb', 'Home > Env Variables')

@section('content')
{{-- Page Header --}}
<div class="flex items-center justify-between mb-6">
    <h5 class="text-lg font-semibold text-zinc-900">{{ __('lang.labels.env_variable.list') }}</h5>
    @can('env-variable-create')
    <a href="{{ route('env-variable.create') }}"
       class="h-10 px-4 rounded-md text-sm font-medium whitespace-nowrap inline-flex items-center gap-2"
       style="background-color: var(--erp-primary); color: var(--erp-primary-fg);">
        <i class="fa-solid fa-plus text-xs"></i> {{ __('lang.labels.env_variable.create') }}
    </a>
    @endcan
</div>

{{-- System Actions Card --}}
<div class="rounded-lg border border-zinc-200 bg-white shadow-sm mb-4">
    <div class="px-4 py-3 border-b border-zinc-200 flex items-center gap-2">
        <i class="fa-solid fa-gear text-zinc-400 text-xs"></i>
        <h6 class="text-sm font-semibold text-zinc-700">{{ __('lang.labels.env_variable.system_actions') }}</h6>
    </div>
    <div class="p-4">
        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 md:col-span-4">
                <button type="button" class="w-full h-10 px-4 rounded-md bg-blue-50 text-blue-700 text-sm font-medium inline-flex items-center justify-center gap-2" onclick="syncToEnvFile()">
                    <i class="fa-solid fa-rotate text-xs"></i> {{ __('lang.labels.env_variable.sync_with_env') }}
                </button>
            </div>
            <div class="col-span-12 md:col-span-4">
                <button type="button" class="w-full h-10 px-4 rounded-md bg-amber-50 text-amber-700 text-sm font-medium inline-flex items-center justify-center gap-2" onclick="clearCache()">
                    <i class="fa-solid fa-trash text-xs"></i> {{ __('lang.labels.env_variable.clear_cache') }}
                </button>
            </div>
            <div class="col-span-12 md:col-span-4">
                <button type="button" class="w-full h-10 px-4 rounded-md bg-zinc-100 text-zinc-700 text-sm font-medium inline-flex items-center justify-center gap-2" onclick="composerDump()">
                    <i class="fa-solid fa-rotate text-xs"></i> {{ __('lang.labels.env_variable.composer_dump') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Environment Variables Table --}}
<div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
    <div class="px-4 py-3 border-b border-zinc-200 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-list text-zinc-400 text-xs"></i>
            <h6 class="text-sm font-semibold text-zinc-700">{{ __('lang.labels.env_variable.list_title') }}</h6>
        </div>
        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium whitespace-nowrap" style="background-color: var(--erp-primary); color: var(--erp-primary-fg);">{{ $envVariables->count() }} {{ __('lang.common.items') }}</span>
    </div>
    <div class="p-4">
        @if ($envVariables->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200">
                            <th class="py-2 px-3 text-left font-medium text-zinc-500">{{ __('lang.labels.env_variable.key') }}</th>
                            <th class="py-2 px-3 text-left font-medium text-zinc-500">{{ __('lang.labels.env_variable.value') }}</th>
                            <th class="py-2 px-3 text-left font-medium text-zinc-500">{{ __('lang.labels.env_variable.description') }}</th>
                            <th class="py-2 px-3 text-left font-medium text-zinc-500">{{ __('lang.labels.env_variable.status') }}</th>
                            <th class="py-2 px-3 text-left font-medium text-zinc-500">{{ __('lang.labels.env_variable.created_by') }}</th>
                            <th class="py-2 px-3 text-left font-medium text-zinc-500">{{ __('lang.labels.env_variable.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($envVariables as $envVar)
                            <tr class="border-b border-zinc-100">
                                <td class="py-2 px-3 text-zinc-900">
                                    <code class="px-1.5 py-0.5 rounded text-xs bg-zinc-100 text-zinc-700">{{ $envVar->key }}</code>
                                    @if ($envVar->is_encrypted)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium whitespace-nowrap bg-amber-50 text-amber-700 ml-1" title="{{ __('lang.labels.env_variable.is_encrypted') }}">
                                            <i class="fa-solid fa-lock" style="font-size:10px;"></i>
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2 px-3">
                                    @if ($envVar->is_encrypted)
                                        <span class="text-zinc-400">
                                            <i class="fa-solid fa-lock" style="font-size:10px;"></i>
                                            {{ __('lang.labels.env_variable.encrypted_value') }}
                                        </span>
                                    @else
                                        <span class="text-zinc-700 inline-block truncate" style="max-width: 200px;" title="{{ $envVar->value }}">
                                            {{ Str::limit($envVar->value, 50) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2 px-3">
                                    <span class="text-zinc-500 inline-block truncate" style="max-width: 300px;" title="{{ $envVar->description }}">
                                        {{ Str::limit($envVar->description, 80) }}
                                    </span>
                                </td>
                                <td class="py-2 px-3">
                                    @if ($envVar->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium whitespace-nowrap bg-emerald-50 text-emerald-700">{{ __('lang.active') }}</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium whitespace-nowrap bg-red-50 text-red-700">{{ __('lang.inactive') }}</span>
                                    @endif
                                </td>
                                <td class="py-2 px-3">
                                    <small class="text-zinc-400">{{ $envVar->createdBy->name ?? 'System' }}</small>
                                </td>
                                <td class="py-2 px-3">
                                    <div class="flex gap-1">
                                        @can('env-variable-list')
                                        <a href="{{ route('env-variable.show', $envVar->public_id ?: $envVar->id) }}" class="py-1.5 px-2.5 rounded-md bg-emerald-50 text-emerald-700 text-xs font-medium whitespace-nowrap inline-flex items-center" title="{{ __('lang.labels.env_variable.view') }}">
                                            <i class="fa-solid fa-eye mr-1" style="font-size:10px;"></i>View
                                        </a>
                                        @endcan
                                        @can('env-variable-edit')
                                        <a href="{{ route('env-variable.edit', $envVar->public_id ?: $envVar->id) }}" class="py-1.5 px-2.5 rounded-md bg-blue-50 text-blue-700 text-xs font-medium whitespace-nowrap inline-flex items-center" title="{{ __('lang.labels.env_variable.edit') }}">
                                            <i class="fa-solid fa-pen mr-1" style="font-size:10px;"></i>Edit
                                        </a>
                                        @endcan
                                        @can('env-variable-delete')
                                        <button type="button" class="py-1.5 px-2.5 rounded-md bg-red-50 text-red-700 text-xs font-medium whitespace-nowrap inline-flex items-center delete" data-id="{{ $envVar->public_id ?: $envVar->id }}" data-name="{{ $envVar->key }}" data-route="{{ route('env-variable.destroy', $envVar->public_id ?: $envVar->id) }}" title="{{ __('lang.labels.env_variable.delete') }}">
                                            <i class="fa-solid fa-trash mr-1" style="font-size:10px;"></i>Delete
                                        </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <i class="fa-solid fa-database text-zinc-300" style="font-size:48px;"></i>
                <h5 class="mt-3 text-zinc-700">{{ __('lang.labels.env_variable.no_records') }}</h5>
                <p class="text-zinc-400 text-sm">{{ __('envvariable::message.create_your_first') }}</p>
                @can('env-variable-create')
                <a href="{{ route('env-variable.create') }}" class="mt-3 inline-flex items-center gap-2 h-10 px-4 rounded-md text-sm font-medium" style="background-color: var(--erp-primary); color: var(--erp-primary-fg);">
                    <i class="fa-solid fa-plus text-xs"></i> {{ __('lang.labels.env_variable.create_new') }}
                </a>
                @endcan
            </div>
        @endif
    </div>
</div>
@endsection

@section('pagescript')
<script>
function syncToEnvFile() {
    erpConfirm({
        title: '{{ __('lang.labels.env_variable.sync_with_env') }}',
        message: '{{ __('lang.common.confirm_sync_env') }}',
        confirmText: '{{ __('lang.labels.env_variable.sync_with_env') }}',
        onConfirm: function() {
            $.post('{{ route('env-variable.sync-to-env') }}', {
                _token: '{{ csrf_token() }}'
            }).done(function(response) {
                if (response.status_code === 200) {
                    erpToast({ title: 'Success', message: response.message, type: 'success' });
                } else {
                    erpToast({ title: 'Error', message: response.message, type: 'error' });
                }
            }).fail(function() {
                erpToast({ title: 'Error', message: '{{ __('lang.common.something_went_wrong') }}', type: 'error' });
            });
        }
    });
}

function clearCache() {
    erpConfirm({
        title: '{{ __('lang.labels.env_variable.clear_cache') }}',
        message: '{{ __('lang.common.confirm_clear_cache') }}',
        confirmText: '{{ __('lang.labels.env_variable.clear_cache') }}',
        type: 'destructive',
        onConfirm: function() {
            $.post('{{ route('env-variable.clear-cache') }}', {
                _token: '{{ csrf_token() }}'
            }).done(function(response) {
                if (response.status_code === 200) {
                    erpToast({ title: 'Success', message: response.message, type: 'success' });
                } else {
                    erpToast({ title: 'Error', message: response.message, type: 'error' });
                }
            }).fail(function() {
                erpToast({ title: 'Error', message: '{{ __('lang.common.something_went_wrong') }}', type: 'error' });
            });
        }
    });
}

function composerDump() {
    erpConfirm({
        title: '{{ __('lang.labels.env_variable.composer_dump') }}',
        message: '{{ __('lang.common.confirm_composer_dump') }}',
        confirmText: '{{ __('lang.labels.env_variable.composer_dump') }}',
        onConfirm: function() {
            $.post('{{ route('env-variable.composer-dump') }}', {
                _token: '{{ csrf_token() }}'
            }).done(function(response) {
                if (response.status_code === 200) {
                    erpToast({ title: 'Success', message: response.message, type: 'success' });
                } else {
                    erpToast({ title: 'Error', message: response.message, type: 'error' });
                }
            }).fail(function() {
                erpToast({ title: 'Error', message: '{{ __('lang.common.something_went_wrong') }}', type: 'error' });
            });
        }
    });
}
</script>
@endsection
