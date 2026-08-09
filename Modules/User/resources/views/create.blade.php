@extends('layouts.app-tw')
@section('title', __('user::message.add'))
@section('nav-module', 'users')
@section('breadcrumb', __('user::message.add'))

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ __('user::message.add') }}</h1>
        <p class="text-sm text-zinc-500 mt-1">Create a new system user account</p>
    </div>
    @can('users-list')
    <a href="{{ route('users.index') }}" class="h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center w-fit">
        <i class="fa-solid fa-arrow-left mr-1.5 text-xs"></i> {{ __('message.common.back') }}
    </a>
    @endcan
</div>

<form action="{{ route('users.store') }}" method="POST" id="userForm" enctype="multipart/form-data" novalidate>
    @csrf
    <input type="hidden" name="user_id" value="">

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
                        <option value="Mr." {{ old('name_prefix', 'Mr.') == 'Mr.' ? 'selected' : '' }}>Mr.</option>
                        <option value="Mrs." {{ old('name_prefix') == 'Mrs.' ? 'selected' : '' }}>Mrs.</option>
                        <option value="Ms." {{ old('name_prefix') == 'Ms.' ? 'selected' : '' }}>Ms.</option>
                        <option value="Dr." {{ old('name_prefix') == 'Dr.' ? 'selected' : '' }}>Dr.</option>
                        <option value="Shri" {{ old('name_prefix') == 'Shri' ? 'selected' : '' }}>Shri</option>
                        <option value="Smt." {{ old('name_prefix') == 'Smt.' ? 'selected' : '' }}>Smt.</option>
                    </select>
                    @if ($errors->has('name_prefix'))
                        <p class="mt-1 text-xs text-red-500">{{ $errors->first('name_prefix') }}</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.first_name') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="firstname" id="firstname" required
                           class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                           placeholder="{{ __('user::message.enter_first_name') }}" value="{{ old('firstname') }}">
                    @if ($errors->has('firstname'))
                        <p class="mt-1 text-xs text-red-500">{{ $errors->first('firstname') }}</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.last_name') }}</label>
                    <input type="text" name="lastname" id="lastname"
                           class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                           placeholder="{{ __('user::message.enter_last_name') }}" value="{{ old('lastname') }}">
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
                           placeholder="{{ __('user::message.enter_valid_email') }}" value="{{ old('email') }}">
                    @if ($errors->has('email'))
                        <p class="mt-1 text-xs text-red-500">{{ $errors->first('email') }}</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.mobile_number') }} <span class="text-red-500">*</span></label>
                    <input type="tel" name="mobile" id="mobile" required maxlength="15"
                           class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                           placeholder="{{ __('user::message.enter_mobile') }}" value="{{ old('mobile') }}">
                    @if ($errors->has('mobile'))
                        <p class="mt-1 text-xs text-red-500">{{ $errors->first('mobile') }}</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.user_name') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="username" id="username" required
                           class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm lowercase focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                           placeholder="{{ __('user::message.enter_username') }}" value="{{ old('username') }}" autocomplete="off"
                           oninput="this.value = this.value.replace(/\s/g, '')">
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
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.city') }}</label>
                    <select name="city_id" id="city_id"
                            data-placeholder="— Select —"
                            class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                        <option value=""></option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.profile_photo') }}</label>
                    <div class="flex items-center gap-3">
                        <div id="profile_photo_preview" class="h-9 w-9 rounded-md bg-zinc-100 flex items-center justify-center shrink-0 border border-zinc-200 overflow-hidden">
                            <i class="fa-solid fa-user text-zinc-400 text-sm"></i>
                        </div>
                        <input type="file" name="profile_photo" id="profile_photo" accept="image/*"
                               class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                    </div>
                    @if ($errors->has('profile_photo'))
                        <p class="mt-1 text-xs text-red-500">{{ $errors->first('profile_photo') }}</p>
                    @endif
                </div>
            </div>

            {{-- Row 4: Password | Confirm Password | Date of Birth --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.password') }} <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="password" name="password" id="user_password" required minlength="8"
                               class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                               placeholder="{{ __('user::message.enter_password') }}" autocomplete="new-password">
                        <button type="button" class="toggle-password absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-zinc-400 hover:text-zinc-600" tabindex="-1">
                            <i class="fa-solid fa-eye-slash text-xs"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.confirm_password') }} <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="password" name="confirm_password" id="user_confirm_password" required minlength="8"
                               class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                               placeholder="{{ __('user::message.enter_confirm_password') }}" autocomplete="new-password">
                        <button type="button" class="toggle-password absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-zinc-400 hover:text-zinc-600" tabindex="-1">
                            <i class="fa-solid fa-eye-slash text-xs"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.dob') }}</label>
                    <input type="text" name="dateofbirth" id="dateofbirth"
                           class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm flatpickr-date focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                           placeholder="DD-MM-YYYY" value="{{ old('dateofbirth') }}" autocomplete="off">
                </div>
            </div>

            {{-- Row 4: Parent User | Role | Status --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.parent_user') }} <span class="text-red-500">*</span></label>
                    <select name="parent_id" id="parent_id" required
                            class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                        <option value="">{{ __('message.common.select') }}</option>
                        @foreach ($parentUsers as $pu)
                            <option value="{{ $pu->id }}" {{ old('parent_id') == $pu->id ? 'selected' : '' }}>{{ $pu->email }}</option>
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
                            <option value="{{ $role }}" {{ old('roles') && in_array($role, old('roles')) ? 'selected' : '' }}>{{ str_replace('_', ' ', $role) }}</option>
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
                        <option value="Active" {{ old('status', 'Active') == 'Active' ? 'selected' : '' }}>{{ __('message.common.active') }}</option>
                        <option value="InActive" {{ old('status') == 'InActive' ? 'selected' : '' }}>{{ __('message.common.inactive') }}</option>
                    </select>
                </div>
            </div>

            {{-- Row 5: Current PG --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.current_pg') }}</label>
                    <select name="current_pg" id="current_pg"
                            class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                        <option value="">{{ __('message.common.select') }}</option>
                        @foreach ($pgList as $pg)
                            <option value="{{ $pg->id }}" {{ old('current_pg') == $pg->id ? 'selected' : '' }}>{{ $pg->pg_name }}</option>
                        @endforeach
                    </select>
                    @if ($errors->has('current_pg'))
                        <p class="mt-1 text-xs text-red-500">{{ $errors->first('current_pg') }}</p>
                    @endif
                </div>
            </div>

            {{-- Address --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.address') }}</label>
                <textarea name="address" id="address" rows="3"
                          class="w-full rounded-md border border-zinc-200 bg-transparent px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                          placeholder="{{ __('user::message.enter_address') }}">{{ old('address') }}</textarea>
            </div>

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
    // Toggle password visibility
    $('#userForm .toggle-password').on('click', function() {
        var $input = $(this).closest('.relative').find('input');
        var $icon = $(this).find('i');
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $icon.removeClass('fa-eye-slash').addClass('fa-eye');
        } else {
            $input.attr('type', 'password');
            $icon.removeClass('fa-eye').addClass('fa-eye-slash');
        }
    });

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
        // Wait for state to be initialized by data-fresh-prefetch auto-init
        var check = setInterval(function() {
            if ($state.next('.erp-select-wrapper').length) {
                clearInterval(check);
                // State is ready — attach change handler
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
                        cityInst.setValue('');
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
        initErpSelect('#current_pg', { allowClear: true, placeholder: '{{ __("message.common.select") }}' });
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

        // Validate password match
        var pw = document.getElementById('user_password');
        var cpw = document.getElementById('user_confirm_password');
        if (pw && cpw && pw.value && cpw.value && pw.value !== cpw.value) {
            if (typeof erpToast === 'function') {
                erpToast({ title: 'Error', message: 'Passwords do not match', type: 'error' });
            }
            cpw.focus();
            return;
        }

        // Client-side validation
        var errors = validateFormFields($form, false);

        // Validate DOB format
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
