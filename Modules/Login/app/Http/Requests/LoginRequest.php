<?php

namespace Modules\Login\Http\Requests;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Subscription\Models\Subscription;
use Modules\User\Listeners\LogUserAuthentication;
use Modules\User\Models\User;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $field = filter_var($this->input('login'), FILTER_VALIDATE_EMAIL)
            ? 'email'
            : (is_numeric($this->input('login')) ? 'mobile' : 'username');

        $credentials = [
            $field => $this->input('login'),
            'password' => $this->input('password'),
        ];

        $user = User::where($field, $this->input('login'))->first();

        if (! $user) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => __('login::message.invalid_credentials'),
            ]);
        }

        if ($user->status !== 'Active') {
            throw ValidationException::withMessages([
                'login' => __('login::message.account_inactive'),
            ]);
        }

        if ($user->is_blocked) {
            throw ValidationException::withMessages([
                'login' => __('login::message.account_blocked'),
            ]);
        }

        if ($user->hasAnyRole(['Tenant', 'Manager'])) {
            throw ValidationException::withMessages([
                'login' => __('login::message.web_access_denied'),
            ]);
        }

        if ($user->hasRole('Pg_Admin')) {
            $activeSub = Subscription::where('email', $user->email)
                ->where('status', 'active')
                ->whereDate('end_date', '>=', now()->toDateString())
                ->exists();

            if (! $activeSub) {
                throw ValidationException::withMessages([
                    'login' => __('login::message.subscription_expired'),
                ]);
            }
        }

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            $user->increment('login_attempts');

            if ($user->login_attempts >= 3) {
                $user->is_blocked = true;
                $user->save();

                // Log the user blocking event
                LogUserAuthentication::logUserBlocked(
                    $user->id,
                    'Account blocked due to multiple failed login attempts'
                );

                throw ValidationException::withMessages([
                    'login' => __('login::message.too_many_attempts'),
                ]);
            }

            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => __('login::message.invalid_credentials'),
            ]);
        }

        // *** SUCCESSFUL LOGIN ***

        // Reset login attempts and clear rate limiter
        $user->login_attempts = 0;
        $user->save();
        RateLimiter::clear($this->throttleKey());

        // Regenerate session (recommended after login)
        $this->session()->regenerate();

        // LOGOUT OTHER DEVICES if enabled in config/env (ENABLE_MULTI_SESSION_LOGOUT).
        // Rehashes the user's password so the AuthenticateSession middleware on the
        // web group invalidates every other active session on its next request.
        if (config('auth.logout_other_devices') == 1) {
            Auth::logoutOtherDevices($this->input('password'));
        }
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('login')).'|'.$this->ip());
    }
}
