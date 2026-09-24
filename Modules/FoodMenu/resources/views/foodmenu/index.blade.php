@extends('layouts.app-tw')
@section('title', __('foodmenu::message.foodmenu_master'))
@section('nav-module', 'foodmenu')
@section('breadcrumb', 'Home > Food Menu > Manage Menu')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ __('foodmenu::message.foodmenu_list') }}</h1>
    </div>
    <div class="flex items-center gap-2">
        @can('foodmenu-create')
        <button type="button" class="h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 whitespace-nowrap inline-flex items-center new-create" onclick="resetInlineModal();$('#inlineModal').removeClass('hidden')">
            <i class="fa-solid fa-plus mr-1.5 text-xs"></i> {{ __('message.common.addNew') }}
        </button>
        @endcan
    </div>
</div>

{{-- Filter Bar --}}
<form id="filter_form" class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm mb-4" onsubmit="return false;">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 lg:items-end">
        <div class="lg:col-span-4">
            <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('message.common.search') }}</label>
            <div class="flex h-9 rounded-md border border-zinc-200 bg-white focus-within:ring-2 focus-within:ring-zinc-900 focus-within:ring-offset-2 overflow-hidden">
                <span class="inline-flex items-center px-3 bg-zinc-50 border-r border-zinc-200 text-zinc-400 text-xs"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" id="filterSearch" name="filter_search" placeholder="{{ __('foodmenu::message.search_placeholder') }}" class="flex-1 min-w-0 bg-transparent px-3 text-sm text-zinc-700 placeholder:text-zinc-400 focus:outline-none">
            </div>
        </div>
        <div class="lg:col-span-3">
            <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('foodmenu::message.pg') }}</label>
            <select name="filter_pg_id" id="filterPgId" style="width:100%;">
                <option value="">{{ __('message.common.select') }}</option>
                @foreach ($pgList as $pg)
                <option value="{{ $pg->id }}">{{ $pg->pg_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="lg:col-span-3">
            <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('foodmenu::message.menu_type') }}</label>
            <select name="filter_menu_type" id="filterMenuType" class="w-full h-9 rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500">
                <option value="">{{ __('message.common.select') }}</option>
                <option value="weekly">{{ __('foodmenu::message.weekly') }}</option>
                <option value="special">{{ __('foodmenu::message.special') }}</option>
            </select>
        </div>
        <div class="lg:col-span-2 flex items-center gap-2 justify-end">
            <button type="button" class="search h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800">{{ __('foodmenu::message.apply') }}</button>
            <button type="button" class="reset h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm text-zinc-500 hover:bg-zinc-50">{{ __('foodmenu::message.reset') }}</button>
        </div>
    </div>
</form>

{{-- DataTable Card --}}
<div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
    <div class="p-4 overflow-x-auto">
        <table id="table" class="display responsive nowrap w-full">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('foodmenu::message.title') }}</th>
                    <th>{{ __('foodmenu::message.pg') }}</th>
                    <th>{{ __('foodmenu::message.menu_type') }}</th>
                    <th>{{ __('foodmenu::message.week_start_date') }}</th>
                    <th>{{ __('message.common.status') }}</th>
                    <th>{{ __('message.common.action') }}</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- Add/Edit Modal --}}
<div id="inlineModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 erp-inline-modal-close"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="relative w-full max-w-2xl rounded-lg border border-zinc-200 bg-white shadow-xl">
            <div class="flex items-center justify-between p-4 border-b border-zinc-200">
                <h3 class="text-lg font-semibold text-zinc-900" id="exampleModalTitle">{{ __('foodmenu::message.add_foodmenu') }}</h3>
                <button type="button" class="text-zinc-400 hover:text-zinc-600 erp-inline-modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div id="body">
                <form id="form" action="javascript:void(0);" method="POST" novalidate>
                    @csrf
                    <div class="p-4 space-y-4">
                        <input type="hidden" name="id" id="id" value="">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="pg_id">
                                    {{ __('foodmenu::message.pg') }}<span class="text-red-500"> *</span>
                                </label>
                                <select name="pg_id" id="pg_id" style="width:100%;">
                                    <option value="">{{ __('message.common.select') }}</option>
                                    @foreach ($pgList as $pg)
                                    <option value="{{ $pg->id }}">{{ $pg->pg_name }}</option>
                                    @endforeach
                                </select>
                                <div class="mt-1 text-sm text-red-500" id="error_pg_id"></div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="title">
                                    {{ __('foodmenu::message.title') }}<span class="text-red-500"> *</span>
                                </label>
                                <input type="text" required
                                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                       name="title" id="title"
                                       placeholder="{{ __('foodmenu::message.enter_title') }}">
                                <div class="mt-1 text-sm text-red-500" id="error_title"></div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-2">{{ __('foodmenu::message.menu_type') }}<span class="text-red-500"> *</span></label>
                            <div class="flex items-center gap-4">
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="menu_type" value="weekly" checked
                                           class="rounded-full border-zinc-300 text-zinc-900 focus:ring-zinc-900">
                                    <span class="text-sm text-zinc-700">{{ __('foodmenu::message.weekly') }}</span>
                                </label>
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="menu_type" value="special"
                                           class="rounded-full border-zinc-300 text-zinc-900 focus:ring-zinc-900">
                                    <span class="text-sm text-zinc-700">{{ __('foodmenu::message.special') }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div id="week_start_date_field">
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="week_start_date">
                                    {{ __('foodmenu::message.week_start_date') }}<span class="text-red-500"> *</span>
                                </label>
                                <input type="date"
                                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                       name="week_start_date" id="week_start_date">
                                <div class="mt-1 text-sm text-red-500" id="error_week_start_date"></div>
                            </div>

                            <div id="special_date_field" class="hidden">
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="special_date">
                                    {{ __('foodmenu::message.special_date') }}<span class="text-red-500"> *</span>
                                </label>
                                <input type="date"
                                       class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                       name="special_date" id="special_date">
                                <div class="mt-1 text-sm text-red-500" id="error_special_date"></div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-zinc-700 mb-1" for="status">
                                    {{ __('message.common.status') }}
                                </label>
                                <select name="status" id="status"
                                        class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500">
                                    <option value="active">{{ __('message.common.active') }}</option>
                                    <option value="inactive">{{ __('message.common.inactive') }}</option>
                                </select>
                                <div class="mt-1 text-sm text-red-500" id="error_status"></div>
                            </div>
                        </div>

                        @php
                            $weekDays = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                            $mealTimes = ['breakfast', 'lunch', 'dinner'];
                        @endphp

                        <div id="weekly_items_section" class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="block text-sm font-semibold text-zinc-700">{{ __('foodmenu::message.weekly') }} — {{ __('foodmenu::message.items') }}</label>
                                <span class="grid grid-cols-3 gap-2 text-center text-xs font-medium text-zinc-400 w-2/3">
                                    @foreach ($mealTimes as $meal)
                                    <span>{{ __('foodmenu::message.' . $meal) }}</span>
                                    @endforeach
                                </span>
                            </div>
                            @foreach ($weekDays as $day)
                            <div class="grid grid-cols-4 gap-2 items-start">
                                <label class="text-xs font-medium text-zinc-500 pt-2">{{ __('foodmenu::message.' . $day) }}</label>
                                @foreach ($mealTimes as $meal)
                                <div class="fm-col rounded-md border border-zinc-200 bg-zinc-50 p-1.5"
                                     data-section="week_items" data-prefix="week_items[{{ $day }}][{{ $meal }}]">
                                    <div class="flex flex-wrap gap-1.5 mb-1.5 min-h-6 fm-chips"></div>
                                    <div class="flex gap-1">
                                        <input type="text"
                                               class="fm-item-input w-full min-w-0 rounded-md border border-zinc-200 bg-white px-2 py-1.5 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                               placeholder="{{ __('foodmenu::message.' . $meal) }}">
                                        <button type="button" title="{{ __('foodmenu::message.add_item') }}"
                                                class="fm-item-add h-8 w-8 shrink-0 rounded-md bg-zinc-900 text-white inline-flex items-center justify-center hover:bg-zinc-800">
                                            <i class="fa-solid fa-plus text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endforeach
                        </div>

                        <div id="special_items_section" class="hidden space-y-2">
                            <label class="block text-sm font-semibold text-zinc-700">{{ __('foodmenu::message.special') }} — {{ __('foodmenu::message.items') }}</label>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach ($mealTimes as $meal)
                                <div>
                                    <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('foodmenu::message.' . $meal) }}</label>
                                    <div class="fm-col rounded-md border border-zinc-200 bg-zinc-50 p-1.5"
                                         data-section="special_items" data-prefix="special_items[{{ $meal }}]">
                                        <div class="flex flex-wrap gap-1.5 mb-1.5 min-h-6 fm-chips"></div>
                                        <div class="flex gap-1">
                                            <input type="text"
                                                   class="fm-item-input w-full min-w-0 rounded-md border border-zinc-200 bg-white px-2 py-1.5 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                                   placeholder="{{ __('foodmenu::message.' . $meal) }}">
                                            <button type="button" title="{{ __('foodmenu::message.add_item') }}"
                                                    class="fm-item-add h-8 w-8 shrink-0 rounded-md bg-zinc-900 text-white inline-flex items-center justify-center hover:bg-zinc-800">
                                                <i class="fa-solid fa-plus text-xs"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 p-4 border-t border-zinc-200">
                        <button type="button" class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center erp-inline-modal-close">
                            {{ __('message.common.cancel') }}
                        </button>
                        <button type="button" id="save" data-route="{{ route('foodmenu.store') }}"
                                class="h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 whitespace-nowrap inline-flex items-center save">
                            <i class="fa-solid fa-check mr-1.5 text-xs"></i>
                            {{ __('message.common.submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- View Modal --}}
<div id="viewModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 erp-inline-modal-close"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="relative flex flex-col w-full max-w-2xl max-h-[85vh] overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-xl">
            <div class="flex items-center justify-between p-4 border-b border-zinc-200 shrink-0">
                <h3 class="text-lg font-semibold text-zinc-900" id="viewModalTitle">{{ __('foodmenu::message.view_foodmenu') }}</h3>
                <button type="button" class="text-zinc-400 hover:text-zinc-600 erp-inline-modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-5 space-y-4 overflow-y-auto">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('foodmenu::message.title') }}</p>
                        <p class="text-base font-semibold text-zinc-900" id="view_title">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('foodmenu::message.pg') }}</p>
                        <p class="text-sm text-zinc-900" id="view_pg_name">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('foodmenu::message.menu_type') }}</p>
                        <p class="text-sm text-zinc-900" id="view_menu_type">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('foodmenu::message.week_start_date') }}</p>
                        <p class="text-sm text-zinc-900" id="view_week_start_date">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('foodmenu::message.special_date') }}</p>
                        <p class="text-sm text-zinc-900" id="view_special_date">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('message.common.created_by') }}</p>
                        <p class="text-sm text-zinc-900" id="view_user_name">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 mb-1">{{ __('message.common.status') }}</p>
                        <p class="text-sm text-zinc-900" id="view_status">-</p>
                    </div>
                </div>

                <div class="border-t border-zinc-200 pt-4" id="view_items_wrap" hidden>
                    <p class="text-xs font-medium text-zinc-500 mb-2">{{ __('foodmenu::message.items') }}</p>
                    <div class="flex flex-wrap gap-2" id="view_day_nav"></div>
                    <div id="view_items" class="space-y-2.5"></div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 p-4 border-t border-zinc-200">
                <button type="button" class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center erp-inline-modal-close">
                    {{ __('message.common.close') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Items Modal --}}
<div id="itemsModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 items-modal-close"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="relative w-full max-w-4xl rounded-lg border border-zinc-200 bg-white shadow-xl max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between p-4 border-b border-zinc-200 shrink-0">
                <h3 class="text-lg font-semibold text-zinc-900" id="itemsModalTitle">{{ __('foodmenu::message.items') }}</h3>
                <button type="button" class="text-zinc-400 hover:text-zinc-600 items-modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-4 flex-1 overflow-y-auto">
                <form id="itemsForm" action="javascript:void(0);" method="POST" novalidate>
                    @csrf
                    <input type="hidden" name="food_menu_id" id="item_food_menu_id" value="">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1" for="day">
                                {{ __('foodmenu::message.day') }}
                            </label>
                            <select name="day" id="day"
                                    class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500">
                                <option value="">—</option>
                                <option value="monday">{{ __('foodmenu::message.monday') }}</option>
                                <option value="tuesday">{{ __('foodmenu::message.tuesday') }}</option>
                                <option value="wednesday">{{ __('foodmenu::message.wednesday') }}</option>
                                <option value="thursday">{{ __('foodmenu::message.thursday') }}</option>
                                <option value="friday">{{ __('foodmenu::message.friday') }}</option>
                                <option value="saturday">{{ __('foodmenu::message.saturday') }}</option>
                                <option value="sunday">{{ __('foodmenu::message.sunday') }}</option>
                            </select>
                            <div class="mt-1 text-sm text-red-500" id="error_day"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1" for="meal_time">
                                {{ __('foodmenu::message.meal_time') }}<span class="text-red-500"> *</span>
                            </label>
                            <select name="meal_time" id="meal_time" required
                                    class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500">
                                <option value="">{{ __('message.common.select') }}</option>
                                <option value="breakfast">{{ __('foodmenu::message.breakfast') }}</option>
                                <option value="lunch">{{ __('foodmenu::message.lunch') }}</option>
                                <option value="dinner">{{ __('foodmenu::message.dinner') }}</option>
                            </select>
                            <div class="mt-1 text-sm text-red-500" id="error_meal_time"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1" for="item_name">
                                {{ __('foodmenu::message.item_name') }}<span class="text-red-500"> *</span>
                            </label>
                            <input type="text" required
                                   class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                   name="item_name" id="item_name"
                                   placeholder="{{ __('foodmenu::message.enter_item_name') }}">
                            <div class="mt-1 text-sm text-red-500" id="error_item_name"></div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-zinc-700 mb-1" for="description">
                                {{ __('foodmenu::message.description') }}
                            </label>
                            <input type="text"
                                   class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500"
                                   name="description" id="description">
                            <div class="mt-1 text-sm text-red-500" id="error_description"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 mb-4">
                        <button type="button" id="saveItem"
                                class="h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 whitespace-nowrap inline-flex items-center">
                            <i class="fa-solid fa-plus mr-1.5 text-xs"></i>
                            {{ __('foodmenu::message.add_item') }}
                        </button>
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table id="itemsTable" class="display responsive nowrap w-full">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('foodmenu::message.day') }}</th>
                                <th>{{ __('foodmenu::message.meal_time') }}</th>
                                <th>{{ __('foodmenu::message.item_name') }}</th>
                                <th>{{ __('message.common.status') }}</th>
                                <th>{{ __('message.common.action') }}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 p-4 border-t border-zinc-200 shrink-0">
                <button type="button" class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center items-modal-close">
                    {{ __('message.common.close') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('pagescript')
<script type="application/javascript">
    'use strict';
    window.URL_ROUTE = "{{ route('foodmenu.index') }}";

    window.validationMessages = {};

    var table = '';
    var itemsTable = null;
    var pgSelectInst = null;
    var filterPgSelectInst = null;
    var activeFoodMenuId = null;

    $(function() {
        table = initErpTable('#table', {
            ajax: {
                url: window.URL_ROUTE,
                data: function (d) {
                    d.filter_search = $('#filterSearch').val();
                    d.filter_pg_id = $('#filterPgId').val();
                    d.filter_menu_type = $('#filterMenuType').val();
                }
            },
            processing: true,
            serverSide: true,
            scrollX: true,
            aLengthMenu: [
                [15, 30, 50, 100, -1],
                [15, 30, 50, 100, "All"]
            ],
            order: [[0, 'desc']],
            columns: [
                { data: 'id', render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; }, orderable: false, width: '50px' },
                { data: 'title', name: 'title' },
                { data: 'pg_name', name: 'pg_name', orderable: false, searchable: false },
                { data: 'menu_type', name: 'menu_type', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : '-'; } },
                { data: 'menu_date', name: 'menu_date', orderable: false, searchable: false, render: function(data) { return data || '-'; } },
                { data: 'status', name: 'status', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : '-'; } },
                { data: 'action', name: 'action', orderable: false, sortable: false, width: '200px' }
            ]
        });

        $(document).on('click', '#filter_form .search', function() {
            table.ajax.reload();
        });

        $(document).on('click', '#filter_form .reset', function() {
            $('#filter_form')[0].reset();
            $('#filter_form').find('select').each(function () {
                if (this._erpSelectInst) this._erpSelectInst.setValue('');
            });
            $('#filterMenuType').val('');
            table.ajax.reload();
        });

        if (typeof initErpSelect === 'function') {
            pgSelectInst = initErpSelect('#pg_id', { allowClear: true, placeholder: '{{ __("message.common.select") }}' });
            filterPgSelectInst = initErpSelect('#filterPgId', { allowClear: true, placeholder: '{{ __("message.common.select") }}' });
        }

        $(document).on('change', 'input[name="menu_type"]', function() {
            if ($(this).val() === 'special') {
                $('#week_start_date_field').addClass('hidden');
                $('#special_date_field').removeClass('hidden');
                $('#weekly_items_section').addClass('hidden');
                $('#special_items_section').removeClass('hidden');
            } else {
                $('#special_date_field').addClass('hidden');
                $('#week_start_date_field').removeClass('hidden');
                $('#special_items_section').addClass('hidden');
                $('#weekly_items_section').removeClass('hidden');
            }
        });

        $(document).on('click', '.manage-items', function(e) {
            e.preventDefault();
            var id = $(this).attr('data-id');
            activeFoodMenuId = id;
            $('#item_food_menu_id').val(id);
            $('#itemsModalTitle').text('{{ __("foodmenu::message.manage_items_title") }} - ' + (id || ''));
            $('#itemsForm')[0].reset();

            if (itemsTable && $.fn.DataTable.isDataTable('#itemsTable')) {
                itemsTable.destroy();
                itemsTable = null;
            }

            itemsTable = initErpTable('#itemsTable', {
                ajax: {
                    url: "{{ route('foodmenu.items') }}",
                    data: function (d) {
                        d.food_menu_id = activeFoodMenuId;
                    }
                },
                processing: true,
                serverSide: true,
                scrollX: true,
                aLengthMenu: [
                    [5, 10, 25, 50, -1],
                    [5, 10, 25, 50, "All"]
                ],
                order: [[0, 'desc']],
                columns: [
                    { data: 'id', render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; }, orderable: false, width: '50px' },
                    { data: 'day', name: 'day', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : '—'; } },
                    { data: 'meal_time', name: 'meal_time', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : '-'; } },
                    { data: 'item_name', name: 'item_name' },
                    { data: 'status', name: 'status', render: function(data) { return data ? data.charAt(0).toUpperCase() + data.slice(1) : '-'; } },
                    { data: 'action', name: 'action', orderable: false, sortable: false, width: '100px' }
                ]
            });

            $('#itemsModal').removeClass('hidden');
        });

        $(document).on('click', '.items-modal-close', function(e) {
            e.preventDefault();
            $('#itemsModal').addClass('hidden');
            activeFoodMenuId = null;
        });

        $('#saveItem').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $form = $('#itemsForm');

            if (validateFormFields($form).length > 0) {
                setButtonError($btn);
                return false;
            }

            if (!activeFoodMenuId) {
                toastr.error('Food menu not selected.', 'Error');
                return false;
            }

            var formData = new FormData($form[0]);

            $.ajax({
                type: 'POST',
                url: "{{ route('foodmenu.items.store') }}",
                data: formData,
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,
                headers: { 'Accept': 'application/json' },
                beforeSend: function () {
                    $form.find('.erp-field-error').remove();
                    $form.find('.erp-form-error-banner').hide();
                    $form.find('.border-red-500').removeClass('border-red-500');
                    $form.find('[id^="error_"]').html('');
                    setButtonLoading($btn);
                },
                success: function (response) {
                    if (response.status_code == 500 || response.status_code == 403 || response.status_code == 404) {
                        resetButtonLoading($btn);
                        showFormError($form, response.message);
                    } else if (response.status_code == 201) {
                        resetButtonLoading($btn);
                        showServerErrors($form, response.errors);
                    } else {
                        resetButtonLoading($btn);
                        toastr.success(response.message, 'Success');
                        $form[0].reset();
                        if (itemsTable && $.fn.DataTable.isDataTable('#itemsTable')) {
                            itemsTable.ajax.reload(null, false);
                        }
                    }
                },
                error: function (xhr) {
                    resetButtonLoading($btn);
                    handleAjaxErrors($form, xhr);
                }
            });
        });

        $(document).on('click', '.delete-item', function(e) {
            e.preventDefault();
            var id = $(this).attr('data-id');
            var url = "{{ route('foodmenu.items.destroy', ':id') }}".replace(':id', id);

            erpConfirm({
                title: 'Confirm Delete',
                message: 'Delete this menu item?',
                confirmText: 'Delete',
                cancelText: 'Cancel',
                type: 'destructive'
            }).then(function(confirmed) {
                if (!confirmed) return;
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    headers: { 'Accept': 'application/json' },
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        id: id
                    },
                    success: function(response) {
                        if (response.status_code == 200) {
                            toastr.success(response.message, 'Success');
                            if (itemsTable && $.fn.DataTable.isDataTable('#itemsTable')) {
                                itemsTable.ajax.reload(null, false);
                            }
                        } else if (response.status_code == 201) {
                            toastr.warning(response.message, 'Warning');
                        } else {
                            toastr.error(response.message || 'Error', 'Error');
                        }
                    },
                    error: function(xhr) {
                        var msg = 'Something went wrong. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        toastr.error(msg, 'Error');
                    }
                });
            });
        });
    });

    function resetInlineModal() {
        $('#inlineModal').addClass('hidden');
        $('#form')[0].reset();
        $('#form').find('.border-red-500').removeClass('border-red-500');
        $('#form').find('.erp-field-error').remove();
        $('#form').find('.erp-form-error-banner').hide();
        $('#error_pg_id').html('');
        $('#error_title').html('');
        $('#error_week_start_date').html('');
        $('#error_special_date').html('');
        $('#error_status').html('');
        $('input[name="menu_type"][value="weekly"]').prop('checked', true);
        $('#special_date_field').addClass('hidden');
        $('#week_start_date_field').removeClass('hidden');
        $('#special_items_section').addClass('hidden');
        $('#weekly_items_section').removeClass('hidden');
        $('#inlineModal').find('.erp-btn-locked').each(function() {
            $(this).css({ opacity: '', pointerEvents: '' }).removeClass('erp-btn-locked').removeData('erp-original-pointer');
        });
        $("#save").attr('data-route', "{{ route('foodmenu.store') }}")
            .removeClass('update').addClass('save')
            .html('<i class="fa-solid fa-check mr-1.5 text-xs"></i> {{ __("message.common.submit") }}')
            .prop('disabled', false)
            .removeAttr('style')
            .removeData('erp-original-html')
            .removeData('erp-original-style');
        $("#exampleModalTitle").html("{{ __('foodmenu::message.add_foodmenu') }}");

        if (pgSelectInst && typeof pgSelectInst.setValue === 'function') {
            pgSelectInst.setValue('');
        }

        $('.fm-col').each(function() {
            $(this).data('items', []);
            fmRenderCol($(this));
        });
    }

    function fmRenderCol($col) {
        var items = $col.data('items') || [];
        var prefix = $col.data('prefix');
        var $chips = $col.find('.fm-chips').empty();
        $col.find('.fm-hidden').remove();

        items.forEach(function(item, i) {
            var $chip = $('<div class="fm-chip inline-flex items-center gap-1 rounded-md border border-zinc-200 bg-white px-2 py-1 text-xs text-zinc-700"></div>')
                .append($('<span class="fm-chip-name"></span>').text(item.item_name))
                .append($('<button type="button" class="fm-chip-edit text-zinc-400 hover:text-zinc-600 p-0.5 inline-flex" title="Edit"><i class="fa-solid fa-pen text-[10px]"></i></button>')
                    .on('click', function(e) { e.preventDefault(); fmEditItem($col, i); }))
                .append($('<button type="button" class="fm-chip-remove text-zinc-400 hover:text-red-500 p-0.5 inline-flex" title="Remove"><i class="fa-solid fa-xmark"></i></button>')
                    .on('click', function(e) { e.preventDefault(); items.splice(i, 1); $col.data('items', items); fmRenderCol($col); }));
            $chips.append($chip);
        });

        items.forEach(function(item, i) {
            if (item.id) {
                $('<input type="hidden" class="fm-hidden">').attr('name', prefix + '[' + i + '][id]').val(item.id).appendTo($col);
            }
            $('<input type="hidden" class="fm-hidden">').attr('name', prefix + '[' + i + '][item_name]').val(item.item_name).appendTo($col);
            $('<input type="hidden" class="fm-hidden">').attr('name', prefix + '[' + i + '][description]').val(item.description || '').appendTo($col);
        });
    }

    function fmAddItem(prefix, item) {
        var $col = $('.fm-col[data-prefix="' + prefix + '"]');
        if ($col.length === 0) {
            return;
        }
        var items = $col.data('items') || [];
        items.push({ id: item.id || '', item_name: item.item_name || '', description: item.description || '' });
        $col.data('items', items);
        fmRenderCol($col);
    }

    function fmEditItem($col, i) {
        var items = $col.data('items') || [];
        var item = items[i];
        var name = prompt('Item name', item.item_name);
        if (name === null || name.trim() === '') {
            return;
        }
        var desc = prompt('Description (optional)', item.description || '');
        if (desc === null) {
            desc = item.description || '';
        }
        items[i] = { id: item.id || '', item_name: name.trim(), description: desc };
        $col.data('items', items);
        fmRenderCol($col);
    }

    $(document).on('click', '.fm-item-add', function(e) {
        e.preventDefault();
        var $col = $(this).closest('.fm-col');
        var $input = $col.find('.fm-item-input');
        var text = $input.val().trim();
        if (!text) {
            return;
        }
        var items = $col.data('items') || [];
        items.push({ id: '', item_name: text, description: '' });
        $col.data('items', items);
        fmRenderCol($col);
        $input.val('').focus();
    });

    $(document).on('keydown', '.fm-item-input', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $(this).closest('.fm-col').find('.fm-item-add').trigger('click');
        }
    });

    @php
        $viewWeekDays = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $viewMealTimes = ['breakfast', 'lunch', 'dinner'];
        $viewMealLabels = [];
        $viewDayLabels = [];
        foreach ($viewMealTimes as $viewMeal) {
            $viewMealLabels[$viewMeal] = __('foodmenu::message.'.$viewMeal);
        }
        foreach ($viewWeekDays as $viewDay) {
            $viewDayLabels[$viewDay] = __('foodmenu::message.'.$viewDay);
        }
        $viewMealColors = [
            'breakfast' => '#2563EB',
            'lunch' => '#B45309',
            'dinner' => '#15803D',
        ];
    @endphp
    var FM_WEEK_DAYS = @json($viewWeekDays);
    var FM_MEAL_TIMES = @json($viewMealTimes);
    var FM_MEAL_LABELS = @json($viewMealLabels);
    var FM_DAY_LABELS = @json($viewDayLabels);

    var FM_MEAL_COLORS = @json($viewMealColors);
    var viewState = { menuType: 'weekly', items: [], day: 'sunday' };

    function viewMealBadge(meal) {
        var color = FM_MEAL_COLORS[meal] || '#71717a';
        var label = FM_MEAL_LABELS[meal] || meal;
        return '<span class="shrink-0 rounded px-2 py-0.5 text-[11px] font-semibold" style="background-color:' + color + '1f;color:' + color + '">' + label + '</span>';
    }

    function viewMealIndex(it) {
        var i = FM_MEAL_TIMES.indexOf(it.meal_time);
        return i === -1 ? 99 : i;
    }

    function viewTile(it) {
        var name = $('<span></span>').text(it.item_name || '').html();
        var desc = it.description
            ? '<p class="text-xs text-zinc-400 mt-0.5">' + $('<span></span>').text(it.description).html() + '</p>'
            : '';
        return '<div class="flex items-start gap-3 rounded-lg border border-zinc-200 bg-white p-2.5">'
            + viewMealBadge(it.meal_time)
            + '<div class="min-w-0"><p class="text-sm font-semibold text-zinc-700">' + name + '</p>' + desc + '</div>'
            + '</div>';
    }

    function renderViewList() {
        var items = (viewState.items || []).slice();
        items.sort(function(a, b) {
            return viewMealIndex(a) - viewMealIndex(b) || (a.sort_order || 0) - (b.sort_order || 0);
        });
        if (viewState.menuType === 'weekly') {
            var day = viewState.day;
            items = items.filter(function(it) { return (it.day || '') === day; });
            if (items.length === 0) {
                $('#view_items').html('<p class="text-sm text-zinc-400">' + FM_DAY_LABELS[day] + ' — ' + "{{ __('foodmenu::message.no_items_for_day') }}" + '</p>');
                return;
            }
        }
        $('#view_items').html(items.map(viewTile).join(''));
    }

    function renderViewDayNav() {
        var $nav = $('#view_day_nav').empty();
        FM_WEEK_DAYS.forEach(function(day) {
            var has = (viewState.items || []).some(function(it) { return (it.day || '') === day; });
            var active = viewState.day === day;
            var $chip = $('<button type="button" class="view-day-chip h-10 px-3.5 rounded-full border text-[13px] whitespace-nowrap inline-flex items-center gap-1.5"></button>')
                .attr('data-day', day)
                .text(FM_DAY_LABELS[day])
                .toggleClass('bg-zinc-900 text-white border-zinc-900 font-semibold', active)
                .toggleClass('bg-white text-zinc-600 border-zinc-200 hover:border-zinc-300 font-medium', !active);
            if (has) {
                $chip.append('<span class="w-1.5 h-1.5 rounded-full" style="background-color:' + (active ? '#ffffff' : '#2563EB') + '"></span>');
            }
            $nav.append($chip);
        });
    }

    function renderViewItems(items, menuType) {
        var $wrap = $('#view_items_wrap');
        if (!items || items.length === 0) {
            $wrap.attr('hidden', true);
            $('#view_day_nav').empty();
            return;
        }
        viewState.menuType = menuType === 'special' ? 'special' : 'weekly';
        viewState.items = items;
        if (viewState.menuType === 'special') {
            $('#view_day_nav').hide();
        } else {
            $('#view_day_nav').show();
            var firstDay = null;
            for (var i = 0; i < FM_WEEK_DAYS.length; i++) {
                if (items.some(function(it) { return (it.day || '') === FM_WEEK_DAYS[i]; })) {
                    firstDay = FM_WEEK_DAYS[i];
                    break;
                }
            }
            viewState.day = firstDay || 'sunday';
            renderViewDayNav();
        }
        renderViewList();
        $wrap.attr('hidden', false);
    }

    $('#view_day_nav').on('click', '.view-day-chip', function(e) {
        e.preventDefault();
        viewState.day = $(this).attr('data-day');
        renderViewDayNav();
        renderViewList();
    });

    $(document).on('click', '.erp-inline-modal-close', function(e) {
        e.preventDefault();
        resetInlineModal();
        resetViewModal();
    });

    function resetViewModal() {
        $('#viewModal').addClass('hidden');
    }

    $(document).on('click', '.view', function(e) {
        e.preventDefault();
        var id = $(this).attr('data-id');
        var url = "{{ route('foodmenu.show', ':id') }}".replace(':id', id);
        $.ajax({
            type: "GET",
            url: url,
            dataType: 'json',
            success: function(response) {
                if (response.status_code == 200) {
                    var d = response.result;
                    $('#view_title').text(d.title || '-');
                    $('#view_pg_name').text(d.pg?.pg_name || d.pg_name || '-');
                    $('#view_menu_type').text(d.menu_type ? d.menu_type.charAt(0).toUpperCase() + d.menu_type.slice(1) : '-');
                    $('#view_week_start_date').text(d.week_start_date || '-');
                    $('#view_special_date').text(d.special_date || '-');
                    $('#view_user_name').text(d.user?.name || d.user_name || '-');
                    $('#view_status').text(d.status ? d.status.charAt(0).toUpperCase() + d.status.slice(1) : '-');
                    renderViewItems(d.items || [], d.menu_type);
                    $('#viewModal').removeClass('hidden');
                } else if (response.status_code == 201 || response.status_code == 404) {
                    toastr.warning(response.message, "Warning");
                } else {
                    toastr.error(response.message, "Error");
                }
            }
        });
    });

    $(document).on('click', '.edit', function(e) {
        e.preventDefault();
        resetInlineModal();
        $("#save").attr('data-route', '').removeClass('save').addClass('update');
        var id = $(this).attr('data-id');
        var url = "{{ route('foodmenu.edit', ':id') }}".replace(':id', id);
        $("#save").attr('data-route', "{{ route('foodmenu.update', ':id') }}".replace(':id', id));
        $.ajax({
            type: "GET",
            url: url,
            dataType: 'json',
            success: function(response) {
                if (response.status_code == 200) {
                    $("#exampleModalTitle").html("{{ __('foodmenu::message.edit_foodmenu') }}");
                    $("#title").val(response.result.title);
                    $("#status").val(response.result.status);
                    $("#id").val(id);
                    if (pgSelectInst && typeof pgSelectInst.setValue === 'function') {
                        pgSelectInst.setValue(response.result.pg_id || '');
                    } else {
                        $("#pg_id").val(response.result.pg_id);
                    }
                    var resultItems = response.result.items || [];
                    if (response.result.menu_type === 'special') {
                        $('input[name="menu_type"][value="special"]').prop('checked', true);
                        $('#week_start_date_field').addClass('hidden');
                        $('#special_date_field').removeClass('hidden');
                        $('#weekly_items_section').addClass('hidden');
                        $('#special_items_section').removeClass('hidden');
                        $('#special_date').val(response.result.special_date || '');
                        $('.fm-col[data-section="special_items"]').each(function() {
                            $(this).data('items', []);
                            fmRenderCol($(this));
                        });
                        $.each(resultItems, function(idx, item) {
                            if (item.meal_time) {
                                fmAddItem('special_items[' + item.meal_time + ']', item);
                            }
                        });
                    } else {
                        $('input[name="menu_type"][value="weekly"]').prop('checked', true);
                        $('#special_date_field').addClass('hidden');
                        $('#week_start_date_field').removeClass('hidden');
                        $('#special_items_section').addClass('hidden');
                        $('#weekly_items_section').removeClass('hidden');
                        $('#week_start_date').val(response.result.week_start_date || '');
                        $('.fm-col[data-section="week_items"]').each(function() {
                            $(this).data('items', []);
                            fmRenderCol($(this));
                        });
                        $.each(resultItems, function(idx, item) {
                            if (item.day && item.meal_time) {
                                fmAddItem('week_items[' + item.day + '][' + item.meal_time + ']', item);
                            }
                        });
                    }
                    $('#inlineModal').removeClass('hidden');
                } else if (response.status_code == 201 || response.status_code == 404) {
                    toastr.warning(response.message, "Warning");
                } else {
                    toastr.error(response.message, "Error");
                }
            },
            error: function(xhr) {
                var msg = 'Something went wrong. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                toastr.error(msg, "Error");
            }
        });
    });
</script>
@endsection