@extends('layouts.app-tw')
@section('title', __('pgmanagement::message.pg_management'))
@section('nav-module', 'pgmanagement')
@section('breadcrumb', __('pgmanagement::message.pg_management'))

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ __('pgmanagement::message.pg_management') }}</h1>
    </div>
    <a href="{{ route('pgmanagement.index') }}" class="h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center w-fit">
        <i class="fa-solid fa-arrow-left mr-1.5 text-xs"></i> {{ __('message.common.back') }}
    </a>
</div>
@endsection
