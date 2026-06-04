@extends('layouts.app-tw')
@section('title', __('subscription::message.subscription'))
@section('nav-module', 'subscription')
@section('breadcrumb', 'Home > Subscription > View')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h5 class="text-lg font-semibold" style="color: var(--erp-text);">{{ __('subscription::message.subscription') }}</h5>
    @can('subscription-list')
    <a href="{{ route('subscription.index') }}" class="erp-modal-btn-secondary">
        <i class="fa-solid fa-arrow-left mr-1.5 text-xs"></i> {{ __('message.common.back') }}
    </a>
    @endcan
</div>

<div class="rounded-lg border bg-white shadow-sm p-5" style="border-color: var(--erp-border); background-color: var(--erp-bg);">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <p class="text-xs font-medium mb-1" style="color: var(--erp-text-secondary);">{{ __('subscription::message.subscriber_name') }}</p>
            <p class="text-sm font-semibold" style="color: var(--erp-text);">{{ $subscription->subscriber_name ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs font-medium mb-1" style="color: var(--erp-text-secondary);">{{ __('subscription::message.email') }}</p>
            <p class="text-sm" style="color: var(--erp-text);">{{ $subscription->email ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs font-medium mb-1" style="color: var(--erp-text-secondary);">{{ __('subscription::message.phone') }}</p>
            <p class="text-sm" style="color: var(--erp-text);">{{ $subscription->phone ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs font-medium mb-1" style="color: var(--erp-text-secondary);">{{ __('subscription::message.plan_type') }}</p>
            <p class="text-sm" style="color: var(--erp-text);">{{ $subscription->plan_type ? ucfirst($subscription->plan_type) : '-' }}</p>
        </div>
        <div>
            <p class="text-xs font-medium mb-1" style="color: var(--erp-text-secondary);">{{ __('subscription::message.start_date') }}</p>
            <p class="text-sm" style="color: var(--erp-text);">{{ $subscription->start_date ? $subscription->start_date->format('d-m-Y') : '-' }}</p>
        </div>
        <div>
            <p class="text-xs font-medium mb-1" style="color: var(--erp-text-secondary);">{{ __('subscription::message.end_date') }}</p>
            <p class="text-sm" style="color: var(--erp-text);">{{ $subscription->end_date ? $subscription->end_date->format('d-m-Y') : '-' }}</p>
        </div>
        <div>
            <p class="text-xs font-medium mb-1" style="color: var(--erp-text-secondary);">{{ __('subscription::message.status') }}</p>
            <p class="text-sm">
                @if ($subscription->status === 'active')
                <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 border border-green-200">{{ ucfirst($subscription->status) }}</span>
                @else
                <span style="color: var(--erp-text);">{{ $subscription->status ? ucfirst($subscription->status) : '-' }}</span>
                @endif
            </p>
        </div>
        <div>
            <p class="text-xs font-medium mb-1" style="color: var(--erp-text-secondary);">{{ __('subscription::message.amount') }}</p>
            <p class="text-sm" style="color: var(--erp-text);">{{ $subscription->amount ? '$' . number_format($subscription->amount, 2) : '-' }}</p>
        </div>
        <div>
            <p class="text-xs font-medium mb-1" style="color: var(--erp-text-secondary);">{{ __('subscription::message.payment_status') }}</p>
            <p class="text-sm" style="color: var(--erp-text);">{{ $subscription->payment_status ? ucfirst($subscription->payment_status) : '-' }}</p>
        </div>
        <div>
            <p class="text-xs font-medium mb-1" style="color: var(--erp-text-secondary);">{{ __('message.common.created_at') }}</p>
            <p class="text-sm" style="color: var(--erp-text);">{{ $subscription->created_at ? $subscription->created_at->format('d-m-Y H:i:s') : '-' }}</p>
        </div>
        <div>
            <p class="text-xs font-medium mb-1" style="color: var(--erp-text-secondary);">{{ __('message.common.updated_at') }}</p>
            <p class="text-sm" style="color: var(--erp-text);">{{ $subscription->updated_at ? $subscription->updated_at->format('d-m-Y H:i:s') : '-' }}</p>
        </div>
    </div>
</div>
@endsection
