@extends('layouts.app-tw')
@section('title', __('user::message.change_password'))
@section('nav-module', 'users')
@section('breadcrumb', __('user::message.change_password'))

@section('content')
<div x-data="changePasswordPage()">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ __('user::message.change_password') }}</h1>
            <p class="text-sm text-zinc-500 mt-1">{{ __('user::message.change_password_subtitle') }}</p>
        </div>
        <a href="{{ route('profile') }}"
           class="h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center w-fit">
            <i class="fa-solid fa-arrow-left mr-1.5 text-xs"></i> {{ __('user::message.my_profile') }}
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
            <div class="p-4 border-b border-zinc-200">
                <h2 class="text-base font-semibold text-zinc-900">{{ __('user::message.update_password') }}</h2>
                <p class="text-sm text-zinc-500 mt-0.5">{{ __('user::message.update_password_subtitle') }}</p>
            </div>
            <form id="changePasswordForm" @submit.prevent="submit" class="p-6 space-y-5" novalidate>
                @csrf
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">
                        {{ __('user::message.current_password') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center w-full rounded-md border bg-white text-sm hover:border-zinc-300 focus-within:ring-2 focus-within:ring-zinc-900 focus-within:ring-offset-2"
                         :class="errors.current_password ? 'border-red-500' : 'border-zinc-200'">
                        <i class="fa-solid fa-lock text-zinc-400 text-xs pl-3 pointer-events-none shrink-0"></i>
                        <input type="password" name="current_password" x-model="form.current_password" required
                               @input="clearError('current_password')"
                               :type="showPasswords.current_password ? 'text' : 'password'"
                               placeholder="{{ __('user::message.current_password') }}"
                               autocomplete="current-password"
                               class="flex-1 min-w-0 bg-transparent px-2 py-2 text-sm text-zinc-900 placeholder:text-zinc-400 focus:outline-none">
                        <button type="button" @click="togglePassword('current_password')"
                                class="px-2 text-zinc-400 hover:text-zinc-600 flex items-center shrink-0" tabindex="-1">
                            <i class="fa-solid text-xs" :class="showPasswords.current_password ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                    <p x-show="errors.current_password" x-text="errors.current_password" class="mt-1 text-xs text-red-500"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">
                        {{ __('user::message.new_password') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center w-full rounded-md border bg-white text-sm hover:border-zinc-300 focus-within:ring-2 focus-within:ring-zinc-900 focus-within:ring-offset-2"
                         :class="errors.password ? 'border-red-500' : 'border-zinc-200'">
                        <i class="fa-solid fa-key text-zinc-400 text-xs pl-3 pointer-events-none shrink-0"></i>
                        <input type="password" name="password" x-model="form.password" required
                               @input="clearError('password')"
                               :type="showPasswords.password ? 'text' : 'password'"
                               placeholder="{{ __('user::message.new_password') }}"
                               autocomplete="new-password"
                               class="flex-1 min-w-0 bg-transparent px-2 py-2 text-sm text-zinc-900 placeholder:text-zinc-400 focus:outline-none">
                        <button type="button" @click="togglePassword('password')"
                                class="px-2 text-zinc-400 hover:text-zinc-600 flex items-center shrink-0" tabindex="-1">
                            <i class="fa-solid text-xs" :class="showPasswords.password ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                    <p x-show="errors.password" x-text="errors.password" class="mt-1 text-xs text-red-500"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">
                        {{ __('user::message.confirm_new_password') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center w-full rounded-md border bg-white text-sm hover:border-zinc-300 focus-within:ring-2 focus-within:ring-zinc-900 focus-within:ring-offset-2"
                         :class="errors.confirm_password ? 'border-red-500' : 'border-zinc-200'">
                        <i class="fa-solid fa-key text-zinc-400 text-xs pl-3 pointer-events-none shrink-0"></i>
                        <input type="password" name="confirm_password" x-model="form.confirm_password" required
                               @input="clearError('confirm_password')"
                               :type="showPasswords.confirm_password ? 'text' : 'password'"
                               placeholder="{{ __('user::message.confirm_new_password') }}"
                               autocomplete="new-password"
                               class="flex-1 min-w-0 bg-transparent px-2 py-2 text-sm text-zinc-900 placeholder:text-zinc-400 focus:outline-none">
                        <button type="button" @click="togglePassword('confirm_password')"
                                class="px-2 text-zinc-400 hover:text-zinc-600 flex items-center shrink-0" tabindex="-1">
                            <i class="fa-solid text-xs" :class="showPasswords.confirm_password ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                    <p x-show="errors.confirm_password" x-text="errors.confirm_password" class="mt-1 text-xs text-red-500"></p>
                </div>
                <div class="flex items-center justify-end gap-2 pt-5 border-t border-zinc-200">
                    <a href="{{ route('profile') }}"
                       class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center">
                        {{ __('user::message.cancel') }}
                    </a>
                    <button type="submit" :disabled="submitting"
                            class="h-9 px-6 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 inline-flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fa-solid mr-1.5 text-xs" :class="submitting ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                        <span x-text="submitting ? '{{ __('user::message.updating') }}' : '{{ __('user::message.update_password_btn') }}'"></span>
                    </button>
                </div>
            </form>
        </div>

        <aside class="rounded-lg border border-zinc-200 bg-white shadow-sm lg:col-span-1 h-full">
            <div class="p-4 border-b border-zinc-200">
                <div class="flex items-center gap-2">
                    <div class="h-7 w-7 rounded-md bg-amber-50 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-lightbulb text-amber-600 text-xs"></i>
                    </div>
                    <h3 class="text-sm font-semibold text-zinc-900">{{ __('user::message.password_tips') }}</h3>
                </div>
                <p class="text-xs text-zinc-500 mt-1">{{ __('user::message.password_tips_subtitle') }}</p>
            </div>
            <ul class="p-4 space-y-2 text-sm text-zinc-700">
                <li class="flex items-start gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-500 mt-1 text-xs shrink-0"></i>
                    <span>{{ __('user::message.password_rule_min_chars') }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-500 mt-1 text-xs shrink-0"></i>
                    <span>{{ __('user::message.password_rule_uppercase') }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-500 mt-1 text-xs shrink-0"></i>
                    <span>{{ __('user::message.password_rule_lowercase') }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-500 mt-1 text-xs shrink-0"></i>
                    <span>{{ __('user::message.password_rule_number') }}</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-500 mt-1 text-xs shrink-0"></i>
                    <span>{{ __('user::message.password_rule_special') }}</span>
                </li>
            </ul>
        </aside>
    </div>
</div>
@endsection

@section('pagescript')
<script>
function changePasswordPage() {
    return {
        form: {
            current_password: '',
            password: '',
            confirm_password: '',
        },
        showPasswords: {
            current_password: false,
            password: false,
            confirm_password: false,
        },
        errors: {
            current_password: '',
            password: '',
            confirm_password: '',
        },
        submitting: false,
        togglePassword(field) {
            this.showPasswords[field] = !this.showPasswords[field];
        },
        clearError(field) {
            this.errors[field] = '';
        },
        validate() {
            var e = {};
            if (!this.form.current_password.trim()) {
                e.current_password = '{{ __('user::message.enter_current_password') }}';
            }
            if (!this.form.password.trim()) {
                e.password = '{{ __('user::message.enter_password') }}';
            } else if (this.form.password.length < 8) {
                e.password = '{{ __('user::message.enter_password_min') }}';
            }
            if (!this.form.confirm_password.trim()) {
                e.confirm_password = '{{ __('user::message.enter_confirm_password') }}';
            } else if (this.form.password && this.form.confirm_password !== this.form.password) {
                e.confirm_password = '{{ __('user::message.password_mismatch') }}';
            }
            this.errors = Object.assign({}, this.errors, e);
            return Object.keys(e).length === 0;
        },
        submit() {
            var self = this;
            this.errors = { current_password: '', password: '', confirm_password: '' };
            if (!this.validate()) return;
            if (self.submitting) return;
            self.submitting = true;

            var formData = new FormData();
            formData.append('current_password', self.form.current_password);
            formData.append('password', self.form.password);
            formData.append('confirm_password', self.form.confirm_password);
            formData.append('_token', '{{ csrf_token() }}');

            fetch('{{ route("change-password") }}', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(function(r) {
                if (r.status === 422) return r.json().then(function(d) { return { status: 422, errors: d.errors }; });
                return r.json().then(function(d) { return { status: 200, data: d }; });
            })
            .then(function(res) {
                if (res.status === 422) {
                    var mapped = { current_password: '', password: '', confirm_password: '' };
                    for (var key in res.errors) {
                        if (mapped.hasOwnProperty(key)) {
                            mapped[key] = res.errors[key][0];
                        }
                    }
                    self.errors = mapped;
                    return;
                }
                var data = res.data;
                if (data.status_code === 200) {
                    if (typeof erpToast === 'function') {
                        erpToast({ title: 'Success', message: data.message, type: 'success' });
                    }
                    setTimeout(function() {
                        window.location.href = '{{ route("profile") }}';
                    }, 1500);
                } else {
                    if (typeof erpToast === 'function') {
                        erpToast({ title: 'Error', message: data.message, type: 'error' });
                    }
                }
            })
            .catch(function() {
                if (typeof erpToast === 'function') {
                    erpToast({ title: 'Error', message: 'Network error. Please try again.', type: 'error' });
                }
            })
            .finally(function() {
                self.submitting = false;
            });
        }
    }
}
</script>
@endsection
