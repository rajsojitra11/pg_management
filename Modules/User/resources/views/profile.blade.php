@extends('layouts.app-tw')
@section('title', __('user::message.my_profile'))
@section('nav-module', 'users')
@section('breadcrumb', __('user::message.my_profile'))

@section('content')
<div x-data="{ tab: 'personal' }">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ __('user::message.my_profile') }}</h1>
            <p class="text-sm text-zinc-500 mt-1">{{ __('user::message.manage_account') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('profile.change-password') }}"
               class="h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center">
                <i class="fa-solid fa-key mr-1.5 text-xs"></i> {{ __('user::message.change_password') }}
            </a>
            <button id="btnEditProfile" @click="tab = 'personal'; setTimeout(() => { var el = document.getElementById('pf_fullname'); if (el) { el.focus(); el.scrollIntoView({ behavior: 'smooth', block: 'center' }); } }, 100)"
               class="h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 inline-flex items-center">
                <i class="fa-solid fa-pen mr-1.5 text-xs"></i> {{ __('user::message.edit_profile') }}
            </button>
        </div>
    </div>

    {{-- HERO --}}
    <div class="rounded-lg border border-zinc-200 bg-white shadow-sm overflow-hidden mb-4">
        <div class="p-5 sm:p-6 flex items-center gap-4">
            <div class="relative shrink-0">
                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-[#eef1fb] to-[#dbe3f7] flex items-center justify-center shadow-[inset_0_0_0_1px_#e4e4e7]">
                    @if($user->profile?->profile_photo)
                        <img src="{{ asset('storage/' . $user->profile->profile_photo) }}" class="w-full h-full rounded-full object-cover">
                    @else
                        <i class="fa-solid fa-user text-[#3D52A0] text-[34px]"></i>
                    @endif
                </div>
                <button type="button" id="btnChangePhoto" class="absolute -bottom-0.5 -right-0.5 h-7 w-7 rounded-full bg-zinc-900 text-white flex items-center justify-center border-2 border-white shadow-sm hover:bg-zinc-800" title="Change photo">
                    <i class="fa-solid fa-camera text-[10px]"></i>
                </button>
                <input type="file" id="profile_photo_input" accept="image/*" class="hidden">
            </div>
            <div class="min-w-0">
                <h2 class="text-lg font-bold text-zinc-900 truncate">{{ $user->name }}</h2>
                <p class="text-sm text-zinc-500 truncate">{{ $user->email }}</p>
                <div class="mt-2 flex items-center gap-2 flex-wrap">
                    @foreach ($user->roles as $role)
                        <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium bg-blue-50 border-blue-200 text-blue-700">
                            <i class="fa-solid fa-user-shield text-[10px] mr-1"></i> {{ $role->name }}
                        </span>
                    @endforeach
                    <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium @if(strtolower($user->status) === 'active') bg-emerald-50 border-emerald-200 text-emerald-700 @else bg-red-50 border-red-200 text-red-700 @endif">
                        <i class="fa-solid fa-circle text-[8px] mr-1"></i> {{ ucfirst($user->status) }}
                    </span>
                    @if(!empty($user->profile->address))
                    <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium bg-zinc-50 border-zinc-200 text-zinc-700">
                        <i class="fa-solid fa-location-dot text-[10px] mr-1"></i> {{ Str::limit($user->profile->address, 30) }}
                    </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- TABS --}}
    <nav class="flex gap-1 p-1.5 bg-white border border-zinc-200 rounded-lg shadow-sm mb-4 overflow-x-auto">
        <button @click="tab = 'personal'" :class="tab === 'personal' ? 'bg-zinc-100 text-zinc-900 border-zinc-200' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-50 border-transparent'" class="inline-flex items-center gap-2 h-9 px-4 rounded-md text-sm font-medium border whitespace-nowrap transition-all duration-150">
            <i class="fa-solid fa-id-card text-xs"></i> {{ __('user::message.personal_info') }}
        </button>
        <button @click="tab = 'activity'" :class="tab === 'activity' ? 'bg-zinc-100 text-zinc-900 border-zinc-200' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-50 border-transparent'" class="inline-flex items-center gap-2 h-9 px-4 rounded-md text-sm font-medium border whitespace-nowrap transition-all duration-150">
            <i class="fa-solid fa-wave-square text-xs"></i> {{ __('user::message.activity') }}
        </button>
        <button @click="tab = 'sessions'" :class="tab === 'sessions' ? 'bg-zinc-100 text-zinc-900 border-zinc-200' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-50 border-transparent'" class="inline-flex items-center gap-2 h-9 px-4 rounded-md text-sm font-medium border whitespace-nowrap transition-all duration-150">
            <i class="fa-solid fa-laptop text-xs"></i> {{ __('user::message.sessions') }}
        </button>
    </nav>

    {{-- ════════════════════════════════════════════
       TAB 1: PERSONAL INFO
       ════════════════════════════════════════════ --}}
    <section x-show="tab === 'personal'" class="space-y-4">
        {{-- Personal Information form --}}
        <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
            <div class="p-4 border-b border-zinc-200 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-zinc-900">{{ __('user::message.personal_information') }}</h3>
                    <p class="text-xs text-zinc-500 mt-0.5">{{ __('user::message.personal_info_subtitle') }}</p>
                </div>
            </div>
            <form id="profileForm" class="p-6 space-y-4 sm:space-y-6">
                @csrf
                <div class="flex gap-4 sm:gap-6">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.full_name') }} <span class="text-red-500">*</span></label>
                        <input type="text" name="fullname" id="pf_fullname"
                               value="{{ old('fullname', trim(($user->profile->firstname ?? '') . ' ' . ($user->profile->lastname ?? ''))) }}"
                               class="w-full h-9 rounded-md border border-zinc-200 bg-transparent px-3 text-sm text-zinc-900 placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.email') }} <span class="text-red-500">*</span></label>
                        <input type="email" name="email" id="pf_email"
                               value="{{ old('email', $user->email) }}"
                               class="w-full h-9 rounded-md border border-zinc-200 bg-transparent px-3 text-sm text-zinc-900 placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                    </div>
                </div>
                <div class="w-1/2">
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.mobile') }}</label>
                    <input type="tel" name="mobile" id="pf_mobile"
                           value="{{ old('mobile', $user->mobile) }}"
                           class="w-full h-9 rounded-md border border-zinc-200 bg-transparent px-3 text-sm text-zinc-900 placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                           @input="document.getElementById('pf_mobile_error')?.classList.add('hidden')">
                    <p id="pf_mobile_error" class="mt-1 text-xs text-red-500 hidden"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('user::message.address') }}</label>
                    <textarea name="address" id="pf_address" rows="3"
                           class="w-full rounded-md border border-zinc-200 bg-transparent px-3 py-2 text-sm text-zinc-900 placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">{{ old('address', $user->profile->address ?? '') }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-2 mt-10 pt-6 border-t border-zinc-200">
                    <button type="button" id="pf_cancel" class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50">{{ __('user::message.cancel') }}</button>
                    <button type="submit" class="h-9 px-6 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 inline-flex items-center">
                        <i class="fa-solid fa-check mr-1.5 text-xs"></i> {{ __('user::message.save_changes') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- Account Information card --}}
        <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
            <div class="p-4 border-b border-zinc-200">
                <h3 class="text-sm font-semibold text-zinc-900">{{ __('user::message.account_information') }}</h3>
                <p class="text-xs text-zinc-500 mt-0.5">{{ __('user::message.account_info_subtitle') }}</p>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <p class="text-xs text-zinc-400 uppercase tracking-wider mb-1">{{ __('user::message.user_name') }}</p>
                    <p class="text-sm font-mono font-medium text-zinc-900">{{ $user->username ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-zinc-400 uppercase tracking-wider mb-1">{{ __('user::message.parent_user') }}</p>
                    <p class="text-sm font-medium text-zinc-900">{{ $user->profile?->parentUser?->email ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-zinc-400 uppercase tracking-wider mb-1">{{ __('user::message.role') }}</p>
                    <p class="text-sm font-medium text-zinc-900">{{ $user->roles->pluck('name')->implode(', ') ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-zinc-400 uppercase tracking-wider mb-1">{{ __('user::message.status') }}</p>
                    <p class="text-sm font-medium text-zinc-900">{{ ucfirst($user->status) }}</p>
                </div>
                <div>
                    <p class="text-xs text-zinc-400 uppercase tracking-wider mb-1">{{ __('user::message.created_at') }}</p>
                    <p class="text-sm font-medium text-zinc-900">{{ $user->created_at ? $user->created_at->format('d M Y') : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-zinc-400 uppercase tracking-wider mb-1">{{ __('user::message.last_password_change') }}</p>
                    <p class="text-sm font-medium text-zinc-900">{{ $lastPasswordChange ? \Carbon\Carbon::parse($lastPasswordChange->created_at)->format('d M Y, h:i A') : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-zinc-400 uppercase tracking-wider mb-1">{{ __('user::message.last_login') }}</p>
                    <p class="text-sm font-medium text-zinc-900">{{ $user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->format('d M Y, h:i A') : '—' }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ════════════════════════════════════════════
       TAB 2: ACTIVITY TIMELINE
       ════════════════════════════════════════════ --}}
    <section x-show="tab === 'activity'" class="space-y-4">
        {{-- Filters --}}
        <form id="activity-filter-form" onsubmit="return false;">
            <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm flex flex-col sm:flex-row sm:items-end gap-3">
                <div class="w-full sm:w-53">
                    <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('user::message.activity_action') }}</label>
                    <select name="action" class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm">
                        <option value="">{{ __('user::message.all_actions') }}</option>
                        <option value="login">Login</option>
                        <option value="login_failed">Login Failed</option>
                        <option value="logout">Logout</option>
                        <option value="created">Created</option>
                        <option value="updated">Updated</option>
                        <option value="deleted">Deleted</option>
                        <option value="password_changed">Password Changed</option>
                        <option value="blocked">Blocked</option>
                        <option value="unblocked">Unblocked</option>
                        <option value="activated">Activated</option>
                        <option value="deactivated">Deactivated</option>
                    </select>
                </div>
                <div class="w-full sm:w-45">
                    <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('user::message.from') }}</label>
                    <input type="text" id="act_s_date" name="s_date" class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500 flatpickr-date" placeholder="dd-mm-yyyy">
                </div>
                <div class="w-full lg:w-45">
                    <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('user::message.to') }}</label>
                    <input type="text" id="act_e_date" name="e_date" class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:ring-1 focus:ring-zinc-500 focus:border-zinc-500 flatpickr-date" placeholder="dd-mm-yyyy">
                </div>
                <div class="flex items-center gap-2 lg:ml-auto">
                    <button type="button" id="act-filter-apply" class="h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 inline-flex items-center">
                         {{ __('user::message.apply') }}
                    </button>
                    <button type="button" id="act-filter-reset" class="h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm text-zinc-500 hover:bg-zinc-50">{{ __('user::message.reset') }}</button>
                </div>
            </div>
        </form>

        <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
            <div class="p-4 border-b border-zinc-200">
                <h3 class="text-sm font-semibold text-zinc-900">{{ __('user::message.activity_timeline') }}</h3>
                <p class="text-xs text-zinc-500 mt-0.5">{{ __('user::message.activity_timeline_subtitle') }}</p>
            </div>
            <div class="px-4 pt-5 pb-2" id="activity-timeline-container">
                @php $lastDate = null; @endphp
                @forelse($activities as $log)
                    @php
                        $createdAt = $log->created_at ? \Carbon\Carbon::parse($log->created_at) : null;
                        $dateKey = $createdAt ? $createdAt->format('Y-m-d') : null;
                        $today = now()->format('Y-m-d');
                        $yesterday = now()->subDay()->format('Y-m-d');
                    @endphp
                    @if($dateKey && $dateKey !== $lastDate)
                        @if($lastDate !== null)
                </div>
                        @endif
                        @php $lastDate = $dateKey; @endphp
                <div class="mb-4">
                    <div class="sticky top-0 z-10 -mx-4 px-4 py-1.5 mb-3">
                        <span class="inline-flex items-center rounded-md border border-zinc-200 bg-zinc-50 px-3 py-1 text-xs font-semibold text-zinc-600 uppercase tracking-wider">
                            @if($dateKey === $today)
                                {{ __('user::message.today') }}
                            @elseif($dateKey === $yesterday)
                                {{ __('user::message.yesterday') }}
                            @else
                                {{ $createdAt->format('d M Y') }}
                            @endif
                        </span>
                    </div>
                    <div class="relative pl-1 border-l-2 border-zinc-100 ml-4 space-y-1">
                    @endif
                        @include('user::partials.activity-item')
                @empty
                    <div class="flex flex-col items-center justify-center py-16 text-center">
                        <div class="h-14 w-14 rounded-full bg-zinc-100 flex items-center justify-center mb-3">
                            <i class="fa-solid fa-clock-rotate-left text-xl text-zinc-300"></i>
                        </div>
                        <p class="text-sm font-medium text-zinc-500">{{ __('user::message.no_activity_found') }}</p>
                        <p class="text-xs text-zinc-400 mt-1">{{ __('user::message.no_activity_subtitle') }}</p>
                    </div>
                @endforelse
                @if($activities->isNotEmpty())
                    </div>
                </div>
                <div class="text-center pb-4" id="activity-load-more-wrap">
                    <button type="button" id="act-load-more"
                        class="h-9 px-5 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-600 hover:bg-zinc-50 inline-flex items-center gap-2">
                        <i class="fa-solid fa-chevron-down text-xs"></i>
                        {{ __('user::message.load_more') }}
                    </button>
                </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ════════════════════════════════════════════
       TAB 3: SESSIONS
       ════════════════════════════════════════════ --}}
    <section x-show="tab === 'sessions'" class="space-y-4">
        {{-- Active Sessions --}}
        <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
            <div class="p-4 border-b border-zinc-200 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-zinc-900">{{ __('user::message.active_sessions') }}</h3>
                    <p class="text-xs text-zinc-500 mt-0.5">{{ __('user::message.active_sessions_subtitle') }}</p>
                </div>
                <form id="logout-everywhere-form" method="POST" action="{{ route('profile.logout-everywhere') }}" onsubmit="return confirm('{{ __('user::message.confirm_logout_everywhere') }}')">
                    @csrf
                    <button type="submit"
                            class="h-8 px-3 rounded-md border border-red-200 bg-red-50 text-red-700 text-xs font-medium hover:bg-red-100 inline-flex items-center whitespace-nowrap">
                        <i class="fa-solid fa-right-from-bracket mr-1.5 text-[10px]"></i> {{ __('user::message.sign_out_everywhere') }}
                    </button>
                </form>
            </div>
            <ul class="divide-y divide-zinc-100">
                @forelse($sessions as $session)
                @php
                    $isCurrent = session()->getId() === $session->id;
                    $agent = $session->user_agent ?? '';
                    $deviceIcon = 'fa-display';
                    if (preg_match('/Mobile|Android|iPhone/', $agent)) $deviceIcon = 'fa-mobile-screen-button';
                    elseif (preg_match('/iPad|Tablet/', $agent)) $deviceIcon = 'fa-tablet-screen-button';
                    $browser = 'Unknown';
                    if (preg_match('/Chrome/i', $agent)) $browser = 'Chrome';
                    elseif (preg_match('/Firefox/i', $agent)) $browser = 'Firefox';
                    elseif (preg_match('/Safari/i', $agent)) $browser = 'Safari';
                    elseif (preg_match('/Edge/i', $agent)) $browser = 'Edge';
                    $os = 'Unknown';
                    if (preg_match('/Windows/i', $agent)) $os = 'Windows';
                    elseif (preg_match('/Mac/i', $agent)) $os = 'macOS';
                    elseif (preg_match('/Linux/i', $agent)) $os = 'Linux';
                    elseif (preg_match('/Android/i', $agent)) $os = 'Android';
                    elseif (preg_match('/iPhone|iPad/', $agent)) $os = 'iOS';
                    $lastActive = $session->last_activity ? \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() : '—';
                @endphp
                <li class="flex items-center gap-4 px-5 py-4 hover:bg-zinc-50 session-item" data-session-id="{{ $session->id }}">
                    <div class="h-10 w-10 rounded-md bg-zinc-100 flex items-center justify-center shrink-0">
                        <i class="fa-solid {{ $deviceIcon }} text-zinc-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-sm font-medium text-zinc-900">{{ $os }} · {{ $browser }}</p>
                            @if($isCurrent)
                            <span class="inline-flex items-center rounded-md border px-1.5 py-0 text-[10px] font-medium bg-emerald-50 border-emerald-200 text-emerald-700">{{ __('user::message.this_device') }}</span>
                            @endif
                        </div>
                        <p class="text-xs text-zinc-500 mt-0.5">{{ $session->ip_address ?? '—' }} · {{ $lastActive }}</p>
                    </div>
                </li>
                @empty
                <li class="p-6 text-center text-sm text-zinc-400">{{ __('user::message.no_sessions_found') }}</li>
                @endforelse
            </ul>
        </div>

        {{-- Login History --}}
        <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
            <div class="p-4 border-b border-zinc-200">
                <h3 class="text-sm font-semibold text-zinc-900">{{ __('user::message.login_history') }}</h3>
                <p class="text-xs text-zinc-500 mt-0.5">{{ __('user::message.login_history_subtitle') }}</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase tracking-wider text-zinc-500 border-b border-zinc-200 bg-zinc-50">
                        <tr>
                            <th class="text-left px-5 py-2.5">{{ __('user::message.date') }}</th>
                            <th class="text-left px-5 py-2.5">{{ __('user::message.device') }}</th>
                            <th class="text-left px-5 py-2.5">{{ __('user::message.ip_address') }}</th>
                            <th class="text-left px-5 py-2.5">{{ __('user::message.location') }}</th>
                            <th class="text-left px-5 py-2.5">{{ __('user::message.status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse($loginHistory as $log)
                        <tr>
                            <td class="px-5 py-3 text-zinc-700 whitespace-nowrap">{{ $log->created_at ? $log->created_at->format('d M Y, h:i A') : '—' }}</td>
                            <td class="px-5 py-3 text-zinc-700">
                                <span class="text-sm">{{ $log->device ?? '—' }}</span>
                                @if($log->browser)
                                <span class="text-xs text-zinc-400 block">{{ $log->browser }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 font-mono text-xs text-zinc-700">{{ $log->ip_address ?? '—' }}</td>
                            <td class="px-5 py-3 text-xs text-zinc-500">{{ $log->location ?? '—' }}</td>
                            <td class="px-5 py-3">
                                @php
                                    $isSuccess = $log->successful ?? (in_array($log->activity, ['login', 'logout']));
                                @endphp
                                <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium @if($isSuccess) bg-emerald-50 border-emerald-200 text-emerald-700 @else bg-red-50 border-red-200 text-red-700 @endif">
                                    {{ $isSuccess ? __('user::message.login_success') : __('user::message.login_failed') }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-5 py-6 text-center text-sm text-zinc-400">{{ __('user::message.no_login_history') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

{{-- Server-side error banner --}}
<div id="profile-error-banner" class="hidden fixed top-4 right-4 z-[9999] max-w-sm rounded-lg border border-red-200 bg-red-50 p-4 shadow-lg">
    <div class="flex items-start gap-2">
        <i class="fa-solid fa-circle-exclamation text-red-500 mt-0.5"></i>
        <div>
            <p class="text-sm font-medium text-red-800">{{ __('user::message.error.update_failed') }}</p>
            <ul id="profile-error-list" class="mt-1 text-xs text-red-700 list-disc list-inside"></ul>
        </div>
        <button onclick="this.parentElement.parentElement.classList.add('hidden')" class="ml-auto text-red-400 hover:text-red-600">&times;</button>
    </div>
</div>
@endsection

@section('pagescript')
<script>
(function () {
    var profileForm = document.getElementById('profileForm');

    // Store original form values for cancel/reset
    var originalValues = {};
    if (profileForm) {
        ['pf_fullname', 'pf_email', 'pf_mobile', 'pf_address'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) originalValues[id] = el.value;
        });
        document.getElementById('pf_cancel')?.addEventListener('click', function () {
            Object.keys(originalValues).forEach(function (id) {
                var el = document.getElementById(id);
                if (el) el.value = originalValues[id];
            });
            var mobileErr = document.getElementById('pf_mobile_error');
            if (mobileErr) mobileErr.classList.add('hidden');
        });
    }

    // ── Avatar Photo Upload ─────────────────────────
    var btnChangePhoto = document.getElementById('btnChangePhoto');
    var photoInput = document.getElementById('profile_photo_input');
    if (btnChangePhoto && photoInput) {
        btnChangePhoto.addEventListener('click', function () {
            photoInput.click();
        });
        photoInput.addEventListener('change', function () {
            if (!this.files || !this.files[0]) return;
            var file = this.files[0];
            var formData = new FormData();
            formData.append('profile_photo', file);
            formData.append('_token', '{{ csrf_token() }}');
            btnChangePhoto.disabled = true;
            btnChangePhoto.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-[10px]"></i>';
            fetch('{{ route("profile.avatar-upload") }}', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status_code === 200) {
                    location.reload();
                } else {
                    if (typeof erpToast === 'function') {
                        erpToast({ title: 'Error', message: data.message || 'Upload failed.', type: 'error' });
                    } else {
                        alert(data.message || 'Upload failed.');
                    }
                }
            })
            .catch(function () {
                if (typeof erpToast === 'function') {
                    erpToast({ title: 'Error', message: 'Network error.', type: 'error' });
                } else {
                    alert('Network error.');
                }
            })
            .finally(function () {
                btnChangePhoto.disabled = false;
                btnChangePhoto.innerHTML = '<i class="fa-solid fa-camera text-[10px]"></i>';
                photoInput.value = '';
            });
        });
    }

    if (profileForm) profileForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = profileForm.querySelector('button[type="submit"]');
        var originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5 text-xs"></i> Saving...';

        var formData = new FormData(profileForm);
        formData.append('_token', '{{ csrf_token() }}');

        fetch('{{ route("profile.update") }}', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.status_code === 200) {
                if (typeof erpToast === 'function') {
                    erpToast({ title: '{{ __("user::message.success.updated") }}', message: data.message, type: 'success' });
                } else {
                    alert(data.message);
                }
            } else if (data.status_code === 422 && data.errors) {
                var mobileErrEl = document.getElementById('pf_mobile_error');
                var hasMobileErr = data.errors.mobile && data.errors.mobile.length;
                if (hasMobileErr && mobileErrEl) {
                    mobileErrEl.textContent = data.errors.mobile[0];
                    mobileErrEl.classList.remove('hidden');
                }
                var otherKeys = Object.keys(data.errors).filter(function (k) { return k !== 'mobile'; });
                if (otherKeys.length) {
                    var errorList = document.getElementById('profile-error-list');
                    var errorBanner = document.getElementById('profile-error-banner');
                    errorList.innerHTML = '';
                    otherKeys.forEach(function (key) {
                        data.errors[key].forEach(function (msg) {
                            var li = document.createElement('li');
                            li.textContent = msg;
                            errorList.appendChild(li);
                        });
                    });
                    errorBanner.classList.remove('hidden');
                    setTimeout(function () { errorBanner.classList.add('hidden'); }, 5000);
                }
            } else {
                if (typeof erpToast === 'function') {
                    erpToast({ title: 'Error', message: data.message || 'Something went wrong.', type: 'error' });
                } else {
                    alert(data.message || 'Something went wrong.');
                }
            }
        })
        .catch(function () {
            if (typeof erpToast === 'function') {
                erpToast({ title: 'Error', message: 'Network error. Please try again.', type: 'error' });
            } else {
                alert('Network error. Please try again.');
            }
        })
        .finally(function () {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    });

    // ── Activity Timeline: Load More ──────────────────────────────
    var actPage = 1;
    var actLoading = false;
    var actLoadMoreBtn = document.getElementById('act-load-more');
    var actContainer = document.getElementById('activity-timeline-container');

    function loadActivities(page, append) {
        if (actLoading) return;
        actLoading = true;
        if (actLoadMoreBtn) {
            actLoadMoreBtn.disabled = true;
            actLoadMoreBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs"></i> Loading...';
        }

        var filterForm = document.getElementById('activity-filter-form');
        var params = new URLSearchParams();
        params.set('page', page);
        params.set('per_page', '20');
        if (filterForm) {
            var action = filterForm.querySelector('[name="action"]').value;
            var sDate = filterForm.querySelector('[name="s_date"]').value;
            var eDate = filterForm.querySelector('[name="e_date"]').value;
            if (action) params.set('action', action);
            if (sDate) params.set('s_date', sDate);
            if (eDate) params.set('e_date', eDate);
        }

        fetch('{{ route("profile.activities") }}?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (append && actContainer) {
                var wrapper = actContainer.querySelector('.ml-4') || actContainer;
                wrapper.insertAdjacentHTML('beforeend', data.html);
            } else if (actContainer) {
                // Remove old items but keep empty state
                var existingItems = actContainer.querySelectorAll('.relative.flex.gap-4');
                existingItems.forEach(function (el) { el.remove(); });
                var emptyState = actContainer.querySelector('.flex.flex-col.items-center');
                if (emptyState) emptyState.remove();
                var loadMoreWrap = document.getElementById('activity-load-more-wrap');
                if (loadMoreWrap) loadMoreWrap.remove();

                if (data.html) {
                    // Remove date group end divs before inserting
                    var wrapper = actContainer.querySelector('.ml-4');
                    if (!wrapper) {
                        // Rebuild structure
                        var dateSections = actContainer.querySelectorAll('[class*="mb-4"]');
                        dateSections.forEach(function (s) { s.remove(); });
                        actContainer.insertAdjacentHTML('beforeend', data.html);
                    } else {
                        wrapper.insertAdjacentHTML('beforeend', data.html);
                    }
                } else {
                    actContainer.innerHTML =
                        '<div class="flex flex-col items-center justify-center py-16 text-center">' +
                        '<div class="h-14 w-14 rounded-full bg-zinc-100 flex items-center justify-center mb-3">' +
                        '<i class="fa-solid fa-clock-rotate-left text-xl text-zinc-300"></i>' +
                        '</div>' +
                        '<p class="text-sm font-medium text-zinc-500">{{ __("user::message.no_activity_found") }}</p>' +
                        '<p class="text-xs text-zinc-400 mt-1">{{ __("user::message.no_activity_subtitle") }}</p>' +
                        '</div>';
                }
            }

            if (data.has_more) {
                if (!document.getElementById('act-load-more')) {
                    var wrap = document.createElement('div');
                    wrap.className = 'text-center pb-4';
                    wrap.id = 'activity-load-more-wrap';
                    wrap.innerHTML =
                        '<button type="button" id="act-load-more" ' +
                        'class="h-9 px-5 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-600 hover:bg-zinc-50 inline-flex items-center gap-2">' +
                        '<i class="fa-solid fa-chevron-down text-xs"></i> {{ __("user::message.load_more") }}</button>';
                    if (actContainer) actContainer.appendChild(wrap);
                }
                actLoadMoreBtn = document.getElementById('act-load-more');
                if (actLoadMoreBtn) {
                    actLoadMoreBtn.disabled = false;
                    actLoadMoreBtn.innerHTML = '<i class="fa-solid fa-chevron-down text-xs"></i> {{ __("user::message.load_more") }}';
                }
            } else {
                var wrap = document.getElementById('activity-load-more-wrap');
                if (wrap) wrap.remove();
                actLoadMoreBtn = null;
            }
            actPage = data.next_page || page + 1;
        })
        .catch(function () {
            if (actLoadMoreBtn) {
                actLoadMoreBtn.disabled = false;
                actLoadMoreBtn.innerHTML = '<i class="fa-solid fa-chevron-down text-xs"></i> {{ __("user::message.load_more") }}';
            }
        })
        .finally(function () {
            actLoading = false;
        });
    }

    // Load more click
    document.addEventListener('click', function (e) {
        if (e.target && e.target.id === 'act-load-more') {
            loadActivities(actPage, true);
        }
    });

    // Filter apply
    var filterApply = document.getElementById('act-filter-apply');
    if (filterApply) {
        filterApply.addEventListener('click', function () {
            actPage = 1;
            loadActivities(1, false);
        });
    }

    // Filter reset
    var filterReset = document.getElementById('act-filter-reset');
    if (filterReset) {
        filterReset.addEventListener('click', function () {
            var filterForm = document.getElementById('activity-filter-form');
            if (filterForm) {
                filterForm.querySelector('[name="action"]').value = '';
                filterForm.querySelector('[name="s_date"]').value = '';
                filterForm.querySelector('[name="e_date"]').value = '';
            }
            actPage = 1;
            loadActivities(1, false);
        });
    }

})();
</script>
@endsection