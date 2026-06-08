@extends('layouts.app-tw')
@section('title', __('user::message.edit'))
@section('nav-module', 'users')
@section('breadcrumb', __('user::message.edit'))

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ __('user::message.edit') }} — {{ $user->name }}</h1>
        <p class="text-sm text-zinc-500 mt-1">Update system user account information</p>
    </div>
    @can('users-list')
    <a href="{{ route('users.index') }}" class="h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center w-fit">
        <i class="fa-solid fa-arrow-left mr-1.5 text-xs"></i> {{ __('message.common.back') }}
    </a>
    @endcan
</div>

<form action="{{ route('users.update', [$user->id]) }}" method="POST" id="userForm" enctype="multipart/form-data" novalidate>
    @csrf
    @method('PUT')
    <input type="hidden" name="user_id" value="{{ $user->id }}">

    <div class="rounded-lg border border-zinc-200 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center gap-2 px-4 py-1.5 border-b" style="background:#3D52A0; border-bottom-color:#324690;">
            <div class="h-6 w-6 rounded flex items-center justify-center" style="background:rgba(255,255,255,.18);">
                <i class="fa-solid fa-user text-white" style="font-size:11px;"></i>
            </div>
            <h2 class="text-sm font-semibold text-white">User Details</h2>
        </div>

        {{-- Server-side error banner --}}
        <div class="erp-form-error-banner rounded-lg border border-red-200 bg-red-50 p-3 mb-4" style="display:none;">
            <div class="flex items-start gap-2">
                <i class="fa-solid fa-circle-exclamation text-red-500 mt-0.5"></i>
                <p class="text-sm text-red-700 erp-form-error-text"></p>
            </div>
        </div>

        <div class="p-6 space-y-4 sm:space-y-6">

            {{-- Row 1: Name Prefix | First Name * | Last Name --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.name_prefix') }} <span class="text-red-500">*</span></label>
                    <select name="name_prefix" id="name_prefix" required
                            class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                        <option value="Mr." {{ old('name_prefix', $user->name_prefix ?? 'Mr.') == 'Mr.' ? 'selected' : '' }}>Mr.</option>
                        <option value="Mrs." {{ old('name_prefix', $user->name_prefix) == 'Mrs.' ? 'selected' : '' }}>Mrs.</option>
                        <option value="Ms." {{ old('name_prefix', $user->name_prefix) == 'Ms.' ? 'selected' : '' }}>Ms.</option>
                        <option value="Dr." {{ old('name_prefix', $user->name_prefix) == 'Dr.' ? 'selected' : '' }}>Dr.</option>
                        <option value="Shri" {{ old('name_prefix', $user->name_prefix) == 'Shri' ? 'selected' : '' }}>Shri</option>
                        <option value="Smt." {{ old('name_prefix', $user->name_prefix) == 'Smt.' ? 'selected' : '' }}>Smt.</option>
                    </select>
                    @if ($errors->has('name_prefix'))
                        <p class="mt-1 text-xs text-red-500">{{ $errors->first('name_prefix') }}</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.first_name') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="firstname" id="firstname" required
                           class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                           placeholder="{{ __('user::message.enter_first_name') }}" value="{{ old('firstname', $userProfile->firstname ?? '') }}">
                    @if ($errors->has('firstname'))
                        <p class="mt-1 text-xs text-red-500">{{ $errors->first('firstname') }}</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.last_name') }}</label>
                    <input type="text" name="lastname" id="lastname"
                           class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                           placeholder="{{ __('user::message.enter_last_name') }}" value="{{ old('lastname', $userProfile->lastname ?? '') }}">
                    @if ($errors->has('lastname'))
                        <p class="mt-1 text-xs text-red-500">{{ $errors->first('lastname') }}</p>
                    @endif
                </div>
            </div>

            {{-- Row 2: Email | Mobile | Username --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.email') }} <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="email" required
                           class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                           placeholder="{{ __('user::message.enter_valid_email') }}" value="{{ old('email', $user->email ?? '') }}">
                    @if ($errors->has('email'))
                        <p class="mt-1 text-xs text-red-500">{{ $errors->first('email') }}</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.mobile_number') }} <span class="text-red-500">*</span></label>
                    <input type="tel" name="mobile" id="mobile" required maxlength="15"
                           class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                           placeholder="{{ __('user::message.enter_mobile') }}" value="{{ old('mobile', $user->mobile ?? '') }}">
                    @if ($errors->has('mobile'))
                        <p class="mt-1 text-xs text-red-500">{{ $errors->first('mobile') }}</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.user_name') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="username" id="username" required
                           class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm lowercase focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                           placeholder="{{ __('user::message.enter_username') }}" value="{{ old('username', $user->username ?? '') }}"
                           autocomplete="off" oninput="this.value = this.value.replace(/\s/g, '')">
                    @if ($errors->has('username'))
                        <p class="mt-1 text-xs text-red-500">{{ $errors->first('username') }}</p>
                    @endif
                </div>
            </div>

            {{-- Row 3: State | City | Profile Image --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.state') }}</label>
                    <select name="state_id" id="state_id"
                            data-fresh-prefetch="{{ route('lookup.states') }}"
                            data-placeholder="— Select —"
                            class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                        <option value=""></option>
                        @if($userProfile->state_id)
                            <option value="{{ $userProfile->state_id }}" selected>{{ $userProfile->state->name ?? '' }}</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.city') }}</label>
                    <select name="city_id" id="city_id"
                            data-placeholder="— Select —"
                            class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                        <option value=""></option>
                        @if($userProfile->city_id)
                            <option value="{{ $userProfile->city_id }}" selected>{{ $userProfile->city->name ?? '' }}</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.profile_photo') }}</label>
                    <div class="flex items-center gap-3">
                        <div id="profile_photo_preview" class="h-9 w-9 rounded-md bg-zinc-100 flex items-center justify-center shrink-0 border border-zinc-200 overflow-hidden">
                            @if($userProfile->profile_photo)
                                <img src="{{ Storage::url($userProfile->profile_photo) }}" class="h-full w-full object-cover">
                            @else
                                <i class="fa-solid fa-user text-zinc-400 text-sm"></i>
                            @endif
                        </div>
                        <input type="file" name="profile_photo" id="profile_photo" accept="image/*"
                               class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                    </div>
                    @if ($errors->has('profile_photo'))
                        <p class="mt-1 text-xs text-red-500">{{ $errors->first('profile_photo') }}</p>
                    @endif
                </div>
            </div>

            {{-- Date of Birth --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.dob') }}</label>
                <input type="text" name="dateofbirth" id="dateofbirth"
                       class="h-9 w-full max-w-sm rounded-md border border-zinc-200 bg-transparent px-3 text-sm flatpickr-date focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                       placeholder="DD-MM-YYYY"
                       value="{{ old('dateofbirth', isset($userProfile->date_of_birth) ? \Carbon\Carbon::parse($userProfile->date_of_birth)->format('d-m-Y') : '') }}"
                       autocomplete="off">
            </div>

            {{-- Row 5: Parent User | Role | Status --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.parent_user') }}</label>
                    <select name="parent_id" id="parent_id"
                            class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                        <option value="">{{ __('message.common.select') }}</option>
                        @foreach ($parentUsers as $pu)
                            <option value="{{ $pu->id }}" {{ old('parent_id', $userProfile->parent_id ?? '') == $pu->id ? 'selected' : '' }}>{{ $pu->email }}</option>
                        @endforeach
                    </select>
                    @if ($errors->has('parent_id'))
                        <p class="mt-1 text-xs text-red-500">{{ $errors->first('parent_id') }}</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.role') }} <span class="text-red-500">*</span></label>
                    <select name="roles[]" id="roles" required
                            class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                        <option value="">{{ __('message.common.select') }}</option>
                        @foreach ($roleMaster as $role)
                            <option value="{{ $role }}" {{ in_array($role, old('roles', $userRole ?? [])) ? 'selected' : '' }}>{{ str_replace('_', ' ', $role) }}</option>
                        @endforeach
                    </select>
                    @if ($errors->has('roles'))
                        <p class="mt-1 text-xs text-red-500">{{ $errors->first('roles') }}</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.status') }} <span class="text-red-500">*</span></label>
                    <select name="status" id="status" required
                            class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                        <option value="Active" {{ old('status', $user->status) == 'Active' ? 'selected' : '' }}>{{ __('message.common.active') }}</option>
                        <option value="InActive" {{ old('status', $user->status) == 'InActive' ? 'selected' : '' }}>{{ __('message.common.inactive') }}</option>
                    </select>
                </div>
            </div>

            {{-- Address --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.address') }}</label>
                <textarea name="address" id="address" rows="3"
                          class="w-full rounded-md border border-zinc-200 bg-transparent px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                          placeholder="{{ __('user::message.enter_address') }}">{{ old('address', $userProfile->address ?? '') }}</textarea>
            </div>

            {{-- Direct Permissions (chip grid — same as Role edit) --}}
            @can('system-administration-access')
            @if (isset($permission) && count($permission) > 0)
            <div class="pt-6 border-t border-zinc-200">
                @include('role::partials.permission-grid', [
                    'permission' => $permission,
                    'rolePermissions' => $rolePermissionIds,
                    'userMode' => true,
                    'directPermissionIds' => $directPermissionIds,
                ])
            </div>
            @endif
            @endcan

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-2 mt-10 pt-6 border-t border-zinc-200">
                <a href="{{ route('users.index') }}" class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center">
                    <i class="fa-solid fa-xmark mr-1.5 text-xs"></i> {{ __('message.common.cancel') }}
                </a>
                <button type="submit" class="h-9 px-6 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 inline-flex items-center">
                    <i class="fa-solid fa-check mr-1.5 text-xs"></i> {{ __('message.common.submit') }}
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@section('pagescript')
<script>
$(document).ready(function() {
    // Flatpickr for Date of Birth
    if ($('#dateofbirth').length) {
        flatpickr('#dateofbirth', {
            dateFormat: 'd-m-Y',
            maxDate: 'today',
            allowInput: true
        });
    }

    // State → City cascade
    (function() {
        var $state = $('#state_id');
        var $city = $('#city_id');
        if (!$state.length || !$city.length) return;

        var cityInst = null;
        var check = setInterval(function() {
            if ($state.next('.erp-select-wrapper').length) {
                clearInterval(check);
                $state.on('change', function() {
                    var val = $(this).val();
                    if (!val) {
                        if (cityInst) { cityInst.setOptions([]); cityInst.setValue(''); }
                        return;
                    }
                    $.get('{{ route("lookup.cities") }}', { state_id: val, limit: 9999 }, function(data) {
                        if (!cityInst) {
                            cityInst = erpSearchSelect('#city_id', { placeholder: '— Select —' });
                        }
                        cityInst.setOptions(data || []);
@if($userProfile->city_id)
                        cityInst.setValue('{{ $userProfile->city_id }}');
@else
                        cityInst.setValue('');
@endif
                    });
                });
            }
        }, 100);
    })();

    // Profile photo preview
    $('#profile_photo').on('change', function() {
        var file = this.files && this.files[0];
        var $preview = $('#profile_photo_preview');
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $preview.html('<img src="' + e.target.result + '" class="h-full w-full object-cover">');
            };
            reader.readAsDataURL(file);
        } else {
            $preview.html('<i class="fa-solid fa-user text-zinc-400 text-sm"></i>');
        }
    });

    // Initialize Select2 on parent user, role, and status dropdowns
    if (typeof initErpSelect === 'function') {
        initErpSelect('#parent_id', { allowClear: true, placeholder: '{{ __("message.common.select") }}' });
        initErpSelect('#roles', { allowClear: true, placeholder: '{{ __("message.common.select") }}' });
        initErpSelect('#status', { allowClear: true, placeholder: '{{ __("message.common.select") }}' });
    }

    // Show server validation errors inline
    function showInlineServerErrors($form, errors) {
        var firstField = null;
        $.each(errors, function(field, messages) {
            var msg = Array.isArray(messages) ? messages[0] : messages;
            var $field = $form.find('[name="' + field + '"]');
            if (!$field.length) $field = $form.find('[name="' + field + '[]"]');
            if ($field.length) {
                addFieldError($field, msg);
                if (!firstField) firstField = $field;
            } else {
                showFormError($form, msg);
            }
        });
        if (firstField) {
            firstField[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    // Validate DD-MM-YYYY
    function isValidDOB(val) {
        if (!val) return true;
        var parts = val.match(/^(\d{2})-(\d{2})-(\d{4})$/);
        if (!parts) return false;
        var d = parseInt(parts[1], 10), m = parseInt(parts[2], 10), y = parseInt(parts[3], 10);
        var date = new Date(y, m - 1, d);
        return date.getFullYear() === y && date.getMonth() === m - 1 && date.getDate() === d && date <= new Date();
    }

    // AJAX form submit
    $('#userForm').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('[type="submit"]');

        var errors = validateFormFields($form, false);

        var $dob = $form.find('#dateofbirth');
        if ($dob.val() && !isValidDOB($dob.val())) {
            addFieldError($dob, 'Please enter a valid date in DD-MM-YYYY format');
            errors.push('dateofbirth');
        }

        if (errors.length > 0) {
            setButtonError($btn);
            return false;
        }

        $form.find('.erp-field-error').remove();
        $form.find('.border-red-500').removeClass('border-red-500');
        $form.find('.erp-form-error-banner').hide();
        setButtonLoading($btn);

        $.ajax({
            type: 'POST',
            url: $form.attr('action'),
            data: new FormData($form[0]),
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function(response) {
                if (response.status_code == 200) {
                    erpToast({ title: 'Success', message: response.message, type: 'success' });
                    if (response.data) {
                        setTimeout(function() { location.href = response.data; }, 500);
                    }
                } else {
                    setButtonError($btn);
                    if (response.errors) {
                        showServerErrors($form, response.errors);
                    } else {
                        showFormError($form, response.message || 'Something went wrong');
                    }
                }
            },
            error: function(xhr) {
                setButtonError($btn);
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    showInlineServerErrors($form, xhr.responseJSON.errors);
                } else {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong. Please try again.';
                    showFormError($form, msg);
                }
            }
        });
    });
});
</script>
@endsection
