@extends('layouts.app-tw')
@section('title', __('setting::message.edit_setting'))
@section('nav-module', 'setting')
@section('breadcrumb', 'Home > Setting > Edit')

@section('content')
{{-- Page Header --}}
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-zinc-900">{{ __('setting::message.edit_setting') }}</h1>
    <a href="{{ route('setting.index') }}"
       class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center">
        <i class="fa-solid fa-arrow-left mr-1.5 text-xs"></i> Back
    </a>
</div>

{{-- Single Form Card --}}
<div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
    <form action="javascript:void(0);" id="form" method="POST" autocomplete="none" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')

        {{-- Company Information --}}
        <div class="p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1" for="company_name">
                    {{ __('setting::message.company_name') }} <span class="text-red-500">*</span>
                </label>
                <input type="text" required
                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500"
                       name="company_name" id="company_name"
                       placeholder="{{ __('setting::message.company_name') }}"
                       value="{{ old('company_name', $setting->company_name) }}">
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
                       value="{{ old('tag_line', $setting->tag_line) }}">
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
                       value="{{ old('gst_number', $setting->gst_number) }}">
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
                       value="{{ old('pancard_number', $setting->pancard_number) }}">
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
                       value="{{ old('tan_number', $setting->tan_number) }}">
                <div class="mt-1 text-sm text-red-500" id="error_tan_number"></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1" for="email">
                    {{ __('setting::message.email') }}
                </label>
                <input type="email"
                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500"
                       name="email" id="email"
                       placeholder="{{ __('setting::message.email') }}"
                       value="{{ old('email', $setting->email) }}">
                <div class="mt-1 text-sm text-red-500" id="error_email"></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1" for="mobile">
                    {{ __('setting::message.mobile') }}
                </label>
                <input type="tel"
                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500 phone-input"
                       name="mobile" id="mobile"
                       placeholder="{{ __('setting::message.mobile') }}"
                       value="{{ old('mobile', $setting->mobile) }}">
                <div class="mt-1 text-sm text-red-500" id="error_mobile"></div>
            </div>
        </div>
        </div>

        {{-- Address --}}
        <div class="p-5 border-t border-zinc-200">
            <label class="block text-sm font-medium text-zinc-700 mb-1" for="address">
                {{ __('setting::message.address') }}
            </label>
            <textarea name="address" id="address" rows="3"
                      class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500"
                      placeholder="{{ __('setting::message.address') }}">{{ old('address', $setting->address) }}</textarea>
            <div class="mt-1 text-sm text-red-500" id="error_address"></div>
        </div>

        {{-- Location --}}
        <div class="p-5 border-t border-zinc-200">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1" for="country_id">
                    {{ __('message.common.country') }} <span class="text-red-500">*</span>
                </label>
                <select name="country_id" id="country_id" required class="hidden">
                    <option value="">{{ __('message.common.select') }}</option>
                    @if(isset($countries))
                        @foreach ($countries as $c)
                        <option value="{{ $c->id }}" {{ old('country_id', $setting->country_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    @endif
                </select>
                <div class="mt-1 text-sm text-red-500" id="error_country_id"></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1" for="state_id">
                    {{ __('message.common.state') }} <span class="text-red-500">*</span>
                </label>
                <select name="state_id" id="state_id" required class="hidden"></select>
                <div class="mt-1 text-sm text-red-500" id="error_state_id"></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1" for="city_id">
                    {{ __('message.common.city') }} <span class="text-red-500">*</span>
                </label>
                <select name="city_id" id="city_id" required class="hidden"></select>
                <div class="mt-1 text-sm text-red-500" id="error_city_id"></div>
            </div>
        </div>
        </div>

        {{-- File Uploads --}}
        <div class="p-5 border-t border-zinc-200">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                         src="@if ($setting->logo){{ asset('setting/logo/' . $setting->logo) }}@else{{ asset('assets/img/avatars/1.png') }}@endif">
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
                         src="@if ($setting->logo_dark){{ asset('setting/logo_dark/' . $setting->logo_dark) }}@else{{ asset('assets/img/avatars/1.png') }}@endif">
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
                         src="@if ($setting->favicon){{ asset('setting/favicon/' . $setting->favicon) }}@else{{ asset('assets/img/avatars/1.png') }}@endif">
                </div>
            </div>
        </div>
        </div>

        {{-- User Remark Field (for UPDATE) --}}
        <div class="p-5 border-t border-zinc-200">
            @include('partials-tw.remarks-field', ['type' => 'update'])
        </div>

        {{-- Actions --}}
        <div class="p-5 border-t border-zinc-200 flex items-center justify-between">
            <a href="{{ route('setting.index') }}"
               class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center">
                {{ __('message.common.cancel') }}
            </a>
            <button type="button" id="save-settings"
                    class="h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 whitespace-nowrap inline-flex items-center save-settings">
                <i class="fa-solid fa-check mr-1.5 text-xs"></i>
                {{ __('message.common.update') }}
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
        user_remark_required: "{{ __('validation.user_remark_required') }}",
        user_remark_min: "{{ __('validation.user_remark_min', ['min' => config('app.min_comment_length', 3)]) }}",
        user_remark_max: "{{ __('validation.user_remark_max', ['max' => config('app.max_comment_length', 1000)]) }}",
    };

    let STATE_ID = "{{ $setting->state_id ?? '0' }}";
    let CITY_ID = "{{ $setting->city_id ?? '0' }}";

    // Phone number filtering
    $('.phone-input').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Image preview on file select
    $('#logo').on('change', function() { previewImage(event, document.getElementById('logo_preview')); });
    $('#logo_dark').on('change', function() { previewImage(event, document.getElementById('logo_dark_preview')); });
    $('#favicon').on('change', function() { previewImage(event, document.getElementById('favicon_preview')); });

    // ── Country → State → City cascade (auto-loads states/cities on edit) ──
    var loc = erpLocationCascade(
        { country: '#country_id', state: '#state_id', city: '#city_id' },
        { country: '{{ old("country_id", $setting->country_id ?? "") }}' || null,
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

        // Validate remarks (required for update)
        var $remark = $form.find('[name="user_remark"]');
        if ($remark.length) {
            var remarkVal = $remark.val() ? $remark.val().trim() : '';
            var minLen = parseInt($remark.attr('data-min-length')) || 3;
            var msgs = window.validationMessages || {};
            if (!remarkVal) {
                addFieldError($remark, msgs.user_remark_required || 'Please provide a reason');
                errors.push('user_remark');
            } else if (remarkVal.length < minLen) {
                addFieldError($remark, msgs.user_remark_min || 'Minimum ' + minLen + ' characters required');
                errors.push('user_remark');
            }
        }

        if (errors.length > 0) {
            return;
        }

        var formData = new FormData($form[0]);
        var $btn = $(this);

        $.ajax({
            type: 'POST',
            url: "{{ route('setting.update', $setting->id) }}",
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
                $btn.html('<i class="fa-solid fa-check mr-1.5 text-xs"></i> {{ __("message.common.update") }}').attr('disabled', false);

                if (response.success) {
                    toastr.success(response.message, "Success");
                    setTimeout(function() {
                        location.href = "{{ route('setting.index') }}";
                    }, 500);
                } else if (response.status_code == 500) {
                    toastr.error("Something went wrong. Please try again.", "Error");
                } else {
                    toastr.success(response.message || "Updated successfully.", "Success");
                    setTimeout(function() {
                        location.href = "{{ route('setting.index') }}";
                    }, 500);
                }
            },
            error: function(xhr) {
                $btn.html('<i class="fa-solid fa-check mr-1.5 text-xs"></i> {{ __("message.common.update") }}').attr('disabled', false);
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
