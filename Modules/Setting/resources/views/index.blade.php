@extends('layouts.app-tw')
@section('title', __('setting::message.setting'))
@section('nav-module', 'setting')
@section('breadcrumb', 'Home > Setting')

@section('content')
{{-- Page Header --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ __('setting::message.setting') }}</h1>
    </div>
</div>

{{-- Form Card --}}
<div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
    <form action="javascript:void(0);" id="form" method="POST" autocomplete="none" enctype="multipart/form-data" novalidate>
        @csrf
        <input type="hidden" name="id" id="id" value="{{ $setting->id ?? '' }}">

        {{-- Company Information --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1" for="company_name">
                    {{ __('setting::message.company_name') }} <span class="text-red-500">*</span>
                </label>
                <input type="text" required
                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500"
                       name="company_name" id="company_name"
                       placeholder="{{ __('setting::message.company_name') }}"
                       value="{{ $setting->company_name ?? '' }}">
                <div class="mt-1 text-sm text-red-500" id="error_company_name"></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1" for="tag_line">
                    {{ __('setting::message.tag_line') }}
                </label>
                <input type="text"
                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500"
                       name="tag_line" id="tag_line"
                       placeholder="{{ __('setting::message.tag_line') }}"
                       value="{{ $setting->tag_line ?? '' }}">
                <div class="mt-1 text-sm text-red-500" id="error_tag_line"></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1" for="gst_number">
                    {{ __('setting::message.gst_number') }}
                </label>
                <input type="text"
                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500"
                       name="gst_number" id="gst_number"
                       placeholder="{{ __('setting::message.gst_number') }}"
                       value="{{ $setting->gst_number ?? '' }}">
                <div class="mt-1 text-sm text-red-500" id="error_gst_number"></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1" for="pancard_number">
                    {{ __('setting::message.pancard_number') }}
                </label>
                <input type="text"
                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500"
                       name="pancard_number" id="pancard_number"
                       placeholder="{{ __('setting::message.pancard_number') }}"
                       value="{{ $setting->pancard_number ?? '' }}">
                <div class="mt-1 text-sm text-red-500" id="error_pancard_number"></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1" for="tan_number">
                    {{ __('setting::message.tan_number') }}
                </label>
                <input type="text"
                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500"
                       name="tan_number" id="tan_number"
                       placeholder="{{ __('setting::message.tan_number') }}"
                       value="{{ $setting->tan_number ?? '' }}">
                <div class="mt-1 text-sm text-red-500" id="error_tan_number"></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1" for="email">
                    {{ __('setting::message.email') }}
                </label>
                <input type="text"
                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500"
                       name="email" id="email"
                       placeholder="{{ __('setting::message.email') }}"
                       value="{{ $setting->email ?? '' }}">
                <div class="mt-1 text-sm text-red-500" id="error_email"></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1" for="mobile">
                    {{ __('setting::message.mobile') }}
                </label>
                <input type="text"
                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500 phone-input"
                       name="mobile" id="mobile"
                       placeholder="Mobile"
                       value="{{ $setting->mobile ?? '' }}">
                <div class="mt-1 text-sm text-red-500" id="error_mobile"></div>
            </div>
        </div>

        {{-- Address --}}
        <div class="mt-4">
            <label class="block text-sm font-medium text-zinc-700 mb-1" for="address">
                {{ __('setting::message.address') }}
            </label>
            <textarea name="address" id="address" rows="3"
                      class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500">{{ old('address', $setting->address ?? '') }}</textarea>
        </div>

        {{-- Location --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1" for="country_id">
                    {{ __('message.common.country') }} <span class="text-red-500">*</span>
                </label>
                <select name="country_id" id="country_id" required class="select-searchable hidden">
                    <option value="">{{ __('message.common.select') }}</option>
                    @foreach (\Modules\Country\Models\Country::orderBy('name')->get(['id', 'name']) as $c)
                        <option value="{{ $c->id }}" @selected(isset($setting) && $setting && $setting->country_id == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
                <div class="mt-1 text-sm text-red-500" id="error_country_id"></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1" for="state_id">
                    {{ __('message.common.state') }} <span class="text-red-500">*</span>
                </label>
                <select name="state_id" id="state_id" required class="hidden"></select>
                <div class="mt-1 text-sm text-red-500" id="error_state"></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1" for="city_id">
                    {{ __('message.common.city') }} <span class="text-red-500">*</span>
                </label>
                <select name="city_id" id="city_id" required class="hidden"></select>
                <div class="mt-1 text-sm text-red-500" id="error_city"></div>
            </div>
        </div>

        {{-- File Uploads --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            {{-- Logo --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1" for="logo">
                    {{ __('setting::message.logo') }}
                </label>
                <input type="file" name="logo" id="logo" accept="image/*"
                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-700">
                <div class="mt-1 text-sm text-red-500" id="error_logo"></div>
                <div class="mt-2 flex items-center justify-center rounded-md p-2" style="background-color: var(--erp-bg);">
                    <img id="logo_preview" class="rounded object-contain" style="max-height: 5rem;"
                         src="@if (!empty($setting->logo)){{ asset('setting/logo/' . $setting->logo) }}@else{{ asset('assets/img/avatars/1.png') }}@endif">
                </div>
            </div>

            {{-- Logo Dark --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1" for="logo_dark">
                    {{ __('setting::message.logo_dark') }}
                </label>
                <input type="file" name="logo_dark" id="logo_dark" accept="image/*"
                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-700">
                <div class="mt-1 text-sm text-red-500" id="error_logo_dark"></div>
                <div class="mt-2 flex items-center justify-center rounded-md p-2" style="background-color: var(--erp-text);">
                    <img id="logo_dark_preview" class="rounded object-contain" style="max-height: 5rem;"
                         src="@if (!empty($setting->logo_dark)){{ asset('setting/logo_dark/' . $setting->logo_dark) }}@else{{ asset('assets/img/avatars/1.png') }}@endif">
                </div>
            </div>

            {{-- Favicon --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1" for="favicon">
                    {{ __('setting::message.favicon') }}
                </label>
                <input type="file" name="favicon" id="favicon" accept="image/*"
                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-700">
                <div class="mt-1 text-sm text-red-500" id="error_favicon"></div>
                <div class="mt-2 flex items-center justify-center rounded-md p-2">
                    <img id="favicon_preview" class="rounded" style="max-height: 45px; max-width: 45px;"
                         src="@if (!empty($setting->favicon)){{ asset('setting/favicon/' . $setting->favicon) }}@else{{ asset('assets/img/avatars/1.png') }}@endif">
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between mt-6 pt-4 border-t border-zinc-200">
            <a href="{{ route('setting.index') }}"
               class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center">
                {{ __('message.common.cancel') }}
            </a>
            <button type="button" id="save-settings"
                    class="h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 whitespace-nowrap inline-flex items-center save-settings">
                <i class="fa-solid fa-check mr-1.5 text-xs"></i>
                {{ __('message.common.submit') }}
            </button>
        </div>
    </form>
</div>
@endsection

@section('pagescript')
<script type="application/javascript">
    'use strict';

    // Validation messages
    window.validationMessages = {

    };

    let STATE_ID = "{{ $setting->state_id ?? '0' }}";
    let CITY_ID = "{{ $setting->city_id ?? '0' }}";

    // Phone number filtering
    document.querySelectorAll('.phone-input').forEach(function(input) {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    });

    // Image preview on file select
    $('#logo').on('change', function() { previewImage(event, document.getElementById('logo_preview')); });
    $('#logo_dark').on('change', function() { previewImage(event, document.getElementById('logo_dark_preview')); });
    $('#favicon').on('change', function() { previewImage(event, document.getElementById('favicon_preview')); });

    // ── Country → State → City cascade (auto-loads states/cities) ──
    var loc = erpLocationCascade(
        { country: '#country_id', state: '#state_id', city: '#city_id' },
        { country: '{{ $setting->country_id ?? "" }}' || null,
          state: STATE_ID != '0' ? STATE_ID : null,
          city: CITY_ID != '0' ? CITY_ID : null },
        {
            token: '{{ csrf_token() }}',
            stateUrl: '{{ route("change-state") }}',
            cityUrl: '{{ route("change-city") }}',
            placeholder: '{{ __("message.common.select") }}'
        }
    );
    var countryInst = loc.country, stateInst = loc.state, cityInst = loc.city;

    // Custom save handler (FormData needed for file uploads)
    $(document).on('click', '.save-settings', function(e) {
        e.preventDefault();

        var $form = $('#form');
        var errors = [];

        // Clear previous validation errors
        $form.find('.erp-field-error').remove();
        $form.find('.border-red-500').removeClass('border-red-500');
        $form.find('.mt-1.text-sm.text-red-500').text('');

        // Check company_name
        if (!$('#company_name').val().trim()) {
            addFieldError($('#company_name'), "{{ __('setting::message.company_name_required') }}");
            errors.push('company_name');
        }

        // Check country/state/city
        ['country_id', 'state_id', 'city_id'].forEach(function(field) {
            if (!$('#' + field).val()) {
                var label = $('label[for="' + field + '"]').text().replace('*', '').trim();
                addFieldError($('#' + field), label + ' is required');
                errors.push(field);
            }
        });

        if (errors.length > 0) {
            return;
        }

        var formData = new FormData($form[0]);
        var $btn = $(this);

        $.ajax({
            type: 'POST',
            url: "{{ route('setting.store') }}",
            data: formData,
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function() {
                $form.find('.mt-1.text-sm.text-red-500').text('');
                $btn.html('<i class="fa-solid fa-spinner fa-spin mr-1.5 text-xs"></i> Wait').attr('disabled', true);
            },
            success: function(response) {
                $btn.html('<i class="fa-solid fa-check mr-1.5 text-xs"></i> {{ __("message.common.submit") }}').attr('disabled', false);

                if (response.status_code == 500) {
                    toastr.error("Something went wrong. Please try again.", "Error");
                } else if (response.status_code == 403) {
                    toastr.warning("Please input proper data.", "Warning");
                } else {
                    toastr.success("Saved successfully.", "Success");
                    setTimeout(function() {
                        location.href = response.data;
                    }, 500);
                }
            },
            error: function(xhr) {
                $btn.html('<i class="fa-solid fa-check mr-1.5 text-xs"></i> {{ __("message.common.submit") }}').attr('disabled', false);
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function(field, messages) {
                        $('#error_' + field).text(messages[0]);
                    });
                } else {
                    toastr.error("Something went wrong. Please try again.", "Error");
                }
            }
        });
    });
</script>
@endsection
