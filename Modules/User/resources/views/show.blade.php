@extends('layouts.app-tw')
@section('title', __('user::message.details'))
@section('nav-module', 'users')
@section('breadcrumb', __('user::message.details'))

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ $user->name }}</h1>
        <p class="text-sm text-zinc-500 mt-1">{{ __('user::message.details') }}</p>
    </div>
    @can('users-list')
    <a href="{{ route('users.index') }}" class="h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center w-fit">
        <i class="fa-solid fa-arrow-left mr-1.5 text-xs"></i> {{ __('message.common.back') }}
    </a>
    @endcan
</div>

<div class="rounded-lg border border-zinc-200 bg-white shadow-sm overflow-hidden">
    <div class="flex items-center gap-2 px-4 py-1.5 border-b" style="background:#3D52A0; border-bottom-color:#324690;">
        <div class="h-6 w-6 rounded flex items-center justify-center" style="background:rgba(255,255,255,.18);">
            <i class="fa-solid fa-user text-white" style="font-size:11px;"></i>
        </div>
        <h2 class="text-sm font-semibold text-white">{{ __('user::message.details') }}</h2>
    </div>

    <div class="p-6 space-y-5">
        {{-- Profile photo + basic info --}}
        <div class="flex items-center gap-5">
            <div class="h-14 w-14 rounded-full bg-zinc-100 flex items-center justify-center border border-zinc-200 overflow-hidden shrink-0">
                @if($userProfile->profile_photo)
                    <img src="{{ asset('storage/' . $userProfile->profile_photo) }}" class="h-full w-full object-cover">
                @else
                    <i class="fa-solid fa-user text-zinc-400 text-lg"></i>
                @endif
            </div>
            <div>
                <h3 class="text-base font-semibold text-zinc-900">{{ $user->name_prefix ? $user->name_prefix.' ' : '' }}{{ $user->name }}</h3>
                <p class="text-sm text-zinc-500">{{ $userProfile->parentUser?->email ?? '—' }}</p>
            </div>
        </div>

        {{-- Details grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-4">
            <div>
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">{{ __('user::message.first_name') }}</p>
                <p class="text-sm text-zinc-800 mt-0.5">{{ $userProfile->firstname ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">{{ __('user::message.last_name') }}</p>
                <p class="text-sm text-zinc-800 mt-0.5">{{ $userProfile->lastname ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">{{ __('user::message.user_name') }}</p>
                <p class="text-sm text-zinc-800 mt-0.5">{{ $user->username ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">{{ __('user::message.email') }}</p>
                <p class="text-sm text-zinc-800 mt-0.5">{{ $user->email ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">{{ __('user::message.mobile_number') }}</p>
                <p class="text-sm text-zinc-800 mt-0.5">{{ $user->mobile ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">{{ __('user::message.dob') }}</p>
                <p class="text-sm text-zinc-800 mt-0.5">{{ $userProfile->date_of_birth ? \Carbon\Carbon::parse($userProfile->date_of_birth)->format('d-m-Y') : '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">{{ __('user::message.parent_user') }}</p>
                <p class="text-sm text-zinc-800 mt-0.5">{{ $userProfile->parentUser?->email ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">{{ __('user::message.role') }}</p>
                <p class="text-sm text-zinc-800 mt-0.5">{{ $userRole ? implode(', ', $userRole) : '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">{{ __('user::message.status') }}</p>
                <p class="text-sm mt-0.5">
                    @if($user->status == 'Active')
                        <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 border border-green-200">{{ __('message.common.active') }}</span>
                    @else
                        <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 border border-red-200">{{ __('message.common.inactive') }}</span>
                    @endif
                </p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">{{ __('user::message.state') }}</p>
                <p class="text-sm text-zinc-800 mt-0.5">{{ $userProfile->state->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">{{ __('user::message.city') }}</p>
                <p class="text-sm text-zinc-800 mt-0.5">{{ $userProfile->city->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">{{ __('user::message.address') }}</p>
                <p class="text-sm text-zinc-800 mt-0.5">{{ $userProfile->address ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">{{ __('user::message.common.created_at') }}</p>
                <p class="text-sm text-zinc-800 mt-0.5">{{ $user->created_at ? $user->created_at->format('d-m-Y H:i') : '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">{{ __('message.common.updated_at') }}</p>
                <p class="text-sm text-zinc-800 mt-0.5">{{ $user->updated_at ? $user->updated_at->format('d-m-Y H:i') : '—' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
