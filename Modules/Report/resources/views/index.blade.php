@extends('layouts.app-tw')
@section('title', __('report::message.report_center'))
@section('nav-module', 'report')
@section('breadcrumb', 'Home > Reports')

@section('content')
<div class="mb-6">
    <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ __('report::message.report_center') }}</h1>
    <p class="text-sm text-zinc-500 mt-1">{{ __('report::message.select_report') }}</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

    <a href="{{ route('report.tenants') }}"
        class="group rounded-lg border border-zinc-200 bg-white p-4 shadow-sm hover:border-zinc-400 hover:shadow-md transition-all">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-rose-600 uppercase tracking-wide">{{ __('report::message.tenant_report') }}</span>
            <div class="h-8 w-8 rounded-md bg-rose-50 group-hover:bg-rose-100 flex items-center justify-center">
                <i class="fa-solid fa-users text-rose-600 text-sm"></i>
            </div>
        </div>
        <p class="text-2xl font-bold text-zinc-900">{{ $counts['tenant'] }}</p>
        <p class="text-xs text-zinc-500 mt-1 inline-flex items-center gap-1">
            {{ __('report::message.view_report') }} <i class="fa-solid fa-arrow-right text-[10px]"></i>
        </p>
    </a>

    <a href="{{ route('report.payments') }}"
        class="group rounded-lg border border-zinc-200 bg-white p-4 shadow-sm hover:border-zinc-400 hover:shadow-md transition-all">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-cyan-600 uppercase tracking-wide">{{ __('report::message.payment_report') }}</span>
            <div class="h-8 w-8 rounded-md bg-cyan-50 group-hover:bg-cyan-100 flex items-center justify-center">
                <i class="fa-solid fa-money-bill-wave text-cyan-600 text-sm"></i>
            </div>
        </div>
        <p class="text-2xl font-bold text-zinc-900">{{ $counts['payment'] }}</p>
        <p class="text-xs text-zinc-500 mt-1 inline-flex items-center gap-1">
            {{ __('report::message.view_report') }} <i class="fa-solid fa-arrow-right text-[10px]"></i>
        </p>
    </a>

    <a href="{{ route('report.complaints') }}"
        class="group rounded-lg border border-zinc-200 bg-white p-4 shadow-sm hover:border-zinc-400 hover:shadow-md transition-all">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-amber-600 uppercase tracking-wide">{{ __('report::message.complaint_report') }}</span>
            <div class="h-8 w-8 rounded-md bg-amber-50 group-hover:bg-amber-100 flex items-center justify-center">
                <i class="fa-solid fa-circle-exclamation text-amber-600 text-sm"></i>
            </div>
        </div>
        <p class="text-2xl font-bold text-zinc-900">{{ $counts['complaint'] }}</p>
        <p class="text-xs text-zinc-500 mt-1 inline-flex items-center gap-1">
            {{ __('report::message.view_report') }} <i class="fa-solid fa-arrow-right text-[10px]"></i>
        </p>
    </a>

    <a href="{{ route('report.maintenance') }}"
        class="group rounded-lg border border-zinc-200 bg-white p-4 shadow-sm hover:border-zinc-400 hover:shadow-md transition-all">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-emerald-600 uppercase tracking-wide">{{ __('report::message.maintenance_report') }}</span>
            <div class="h-8 w-8 rounded-md bg-emerald-50 group-hover:bg-emerald-100 flex items-center justify-center">
                <i class="fa-solid fa-wrench text-emerald-600 text-sm"></i>
            </div>
        </div>
        <p class="text-2xl font-bold text-zinc-900">{{ $counts['maintenance'] }}</p>
        <p class="text-xs text-zinc-500 mt-1 inline-flex items-center gap-1">
            {{ __('report::message.view_report') }} <i class="fa-solid fa-arrow-right text-[10px]"></i>
        </p>
    </a>

</div>
@endsection