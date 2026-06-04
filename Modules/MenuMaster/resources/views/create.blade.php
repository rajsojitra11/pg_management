@extends('layouts.app-tw')
@section('title', __('menumaster::message.add'))
@section('nav-module', 'menumaster')
@section('breadcrumb', 'Home > Menu Master > Create')

@section('content')
{{-- Page Header --}}
<div class="flex items-center justify-between mb-6">
    <h4 class="text-lg font-semibold text-zinc-900">{{ __('menumaster::message.create_new_menu_item') }}</h4>
    <a href="{{ route('menumasters.index') }}"
       class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center gap-2">
        <i class="fa-solid fa-arrow-left text-xs"></i> {{ __('menumaster::message.back_to_menu_list') }}
    </a>
</div>

<form action="{{ route('menumasters.store') }}" method="POST" id="form" autocomplete="off" novalidate>
    @csrf

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 mb-4">
            <ul class="list-disc pl-5 text-sm text-red-600 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Single Form Card --}}
    <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
        {{-- Form Fields --}}
        <div class="p-5">
            <div class="grid grid-cols-12 gap-4">
                {{-- Menu Title --}}
                <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5" for="menu_title">
                        {{ __('menumaster::message.menu_title') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="menu_title" name="menu_title" value="{{ old('menu_title') }}" required
                           placeholder="{{ __('menumaster::message.menu_title') }}"
                           class="w-full h-9 rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500">
                    <div class="mt-1 text-sm text-red-500" id="error_menu_title"></div>
                </div>

                {{-- Menu Icon --}}
                <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5" for="menu_icon">
                        {{ __('menumaster::message.menu_icon') }}
                    </label>
                    <div class="flex">
                        <input type="text" id="menu_icon" name="menu_icon" value="{{ old('menu_icon') }}"
                               placeholder="{{ __('menumaster::message.icon_class_example') }}"
                               class="w-full h-9 rounded-l-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500"
                               style="border-right: none;">
                        <button type="button" onclick="showIconPicker()"
                                class="h-9 px-3 rounded-r-md border border-zinc-200 bg-zinc-50 text-zinc-500 hover:bg-zinc-50 text-sm">
                            <i class="fa-solid fa-icons"></i>
                        </button>
                    </div>
                    <div class="mt-1 text-sm text-red-500" id="error_menu_icon"></div>
                </div>

                {{-- Menu Route --}}
                <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5" for="menu_route">
                        {{ __('menumaster::message.menu_route_url') }}
                    </label>
                    <input type="text" id="menu_route" name="menu_route" value="{{ old('menu_route') }}"
                           placeholder="/admin/dashboard"
                           class="w-full h-9 rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500">
                    <div class="mt-1 text-sm text-red-500" id="error_menu_route"></div>
                </div>

                {{-- Parent Menu --}}
                <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5" for="parent_id">
                        {{ __('menumaster::message.parent_menu') }}
                    </label>
                    <select id="parent_id" name="parent_id"
                            class="w-full h-9 rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700">
                        <option value="">{{ __('menumaster::message.select_parent_menu') }}</option>
                        @foreach ($parentOptions as $option)
                            <option value="{{ $option['id'] }}" {{ old('parent_id') == $option['id'] ? 'selected' : '' }}>
                                {{ $option['title'] }}
                            </option>
                        @endforeach
                    </select>
                    <div class="mt-1 text-sm text-red-500" id="error_parent_id"></div>
                </div>

                {{-- Module Name --}}
                <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5" for="module_name">
                        {{ __('menumaster::message.module_name') }}
                    </label>
                    <input type="text" id="module_name" name="module_name" value="{{ old('module_name') }}"
                           placeholder="admin, inventory, sales, etc." list="modulesList"
                           class="w-full h-9 rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500">
                    <datalist id="modulesList">
                        @foreach ($modules as $module)
                            <option value="{{ $module }}">
                        @endforeach
                    </datalist>
                    <div class="mt-1 text-sm text-red-500" id="error_module_name"></div>
                </div>

                {{-- Permission Required --}}
                <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5" for="if_can">
                        {{ __('menumaster::message.permission_required') }}
                    </label>
                    <input type="text" id="if_can" name="if_can" value="{{ old('if_can') }}"
                           placeholder="manage-users, view-reports, etc."
                           class="w-full h-9 rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500">
                    <div class="mt-1 text-sm text-red-500" id="error_if_can"></div>
                </div>

                {{-- Is Main Menu Toggle --}}
                <div class="col-span-12 sm:col-span-6 lg:col-span-6 flex items-end">
                    <div class="flex items-center gap-3">
                        <label class="text-sm font-medium text-zinc-700">{{ __('menumaster::message.mark_as_main_menu') }}</label>
                        <div class="erp-toggle relative cursor-pointer" style="width:36px;height:20px;background:var(--erp-border);border-radius:9999px;transition:background-color 0.2s;"
                             onclick="var cb=this.querySelector('input');cb.checked=!cb.checked;this.style.backgroundColor=cb.checked?'var(--erp-primary)':'var(--erp-border)';this.querySelector('.erp-toggle-dot').style.transform=cb.checked?'translateX(16px)':'translateX(0)';">
                            <input type="checkbox" name="is_main_menu" id="is_main_menu" value="1" {{ old('is_main_menu') ? 'checked' : '' }} class="absolute" style="opacity:0;width:0;height:0;">
                            <div class="erp-toggle-dot absolute bg-white rounded-full shadow" style="width:16px;height:16px;top:2px;left:2px;transition:transform 0.2s;transform:{{ old('is_main_menu') ? 'translateX(16px)' : 'translateX(0)' }};"></div>
                        </div>
                    </div>
                    <p class="text-xs text-zinc-400 mt-1 ml-0">{{ __('menumaster::message.main_menu_description') }}</p>
                </div>
            </div>
        </div>

        {{-- Live Preview --}}
        <div class="p-5 border-t border-zinc-200">
            <h6 class="text-sm font-semibold text-zinc-700 mb-3">{{ __('menumaster::message.live_preview') }}</h6>
            <div class="rounded-md border border-zinc-200 bg-zinc-50 p-3">
                <div id="menuPreview" class="flex items-center">
                    <i id="previewIcon" class="fas fa-circle mr-2 text-zinc-500"></i>
                    <span id="previewTitle" class="text-zinc-900">Menu Title</span>
                    <span id="previewRoute" class="ml-2 text-zinc-400" style="display: none;"></span>
                    <span id="previewModule" class="ml-2 px-2 py-0.5 text-xs rounded-full whitespace-nowrap bg-zinc-100 text-zinc-500 border border-zinc-200" style="display: none;"></span>
                    <span id="previewPermission" class="ml-1 px-2 py-0.5 text-xs rounded-full whitespace-nowrap bg-blue-50 text-blue-600 border border-blue-200" style="display: none;">
                        <i class="fas fa-lock"></i>
                    </span>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="p-5 border-t border-zinc-200 flex items-center justify-between">
            <a href="{{ route('menumasters.index') }}"
               class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center gap-2">
                <i class="fa-solid fa-xmark text-xs"></i> {{ __('menumaster::message.cancel') }}
            </a>
            <button type="button" id="save"
                    class="h-9 px-4 rounded-md text-sm font-medium whitespace-nowrap inline-flex items-center gap-2 save"
                    style="background-color: var(--erp-primary); color: var(--erp-primary-fg);"
                    data-route="{{ route('menumasters.store') }}">
                <i class="fa-solid fa-check text-xs"></i> {{ __('menumaster::message.create_menu_item') }}
            </button>
        </div>
    </div>
</form>

@include('menumaster::partials.icon-picker')
@endsection

@section('pagescript')
<script>
$(document).ready(function() {
    // Initialize erpSearchSelect for parent_id
    var parentSelect = document.getElementById('parent_id');
    if (parentSelect) {
        var parentOptions = [{ value: '', label: '{{ __('menumaster::message.select_parent_menu') }}' }];
        @foreach ($parentOptions as $option)
            parentOptions.push({ value: String({{ $option['id'] }}), label: {!! json_encode($option['title']) !!} });
        @endforeach
        erpSearchSelect(parentSelect, {
            options: parentOptions,
            placeholder: '{{ __('menumaster::message.select_parent_menu') }}',
            onChange: function(val) {
                parentSelect.value = val;
                $(parentSelect).trigger('change');
            }
        });
    }

    // Live preview updates
    $('#menu_title').on('input', updatePreview);
    $('#menu_icon').on('input', updatePreview);
    $('#menu_route').on('input', updatePreview);
    $('#module_name').on('input', updatePreview);
    $('#if_can').on('input', updatePreview);
    updatePreview();

    // Auto-update is_main_menu based on parent selection
    $('#parent_id').change(function() {
        var hasParent = $(this).val() !== '';
        var cb = document.getElementById('is_main_menu');
        cb.checked = !hasParent;
        updateToggleVisual(cb);
    });

    // Toggle visual init
    var mainMenuCb = document.getElementById('is_main_menu');
    if (mainMenuCb) {
        updateToggleVisual(mainMenuCb);
        mainMenuCb.addEventListener('change', function() { updateToggleVisual(this); });
    }

});

function updateToggleVisual(cb) {
    var toggle = $(cb).closest('.erp-toggle');
    if (!toggle.length) return;
    toggle.css('backgroundColor', cb.checked ? 'var(--erp-primary)' : 'var(--erp-border)');
    toggle.find('.erp-toggle-dot').css('transform', cb.checked ? 'translateX(16px)' : 'translateX(0)');
}

function updatePreview() {
    var title = $('#menu_title').val() || '{{ __('menumaster::message.menu_title') }}';
    var icon = $('#menu_icon').val() || 'fas fa-circle';
    var route = $('#menu_route').val();
    var module = $('#module_name').val();
    var permission = $('#if_can').val();

    $('#previewTitle').text(title);
    $('#previewIcon').attr('class', icon + ' mr-2 text-zinc-500');
    route ? $('#previewRoute').text('(' + route + ')').show() : $('#previewRoute').hide();
    module ? $('#previewModule').text(module).show() : $('#previewModule').hide();
    permission ? $('#previewPermission').attr('title', 'Permission: ' + permission).show() : $('#previewPermission').hide();
}

function showIconPicker() {
    $('#iconPickerModal').show();
}
</script>
@endsection
