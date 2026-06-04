@extends('layouts.app-tw')
@section('title', __('unit::message.list'))
@section('nav-module', 'unit')
@section('breadcrumb', 'Home > Masters > Unit')

@section('content')
{{-- Page Header --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ __('unit::message.list') }}</h1>
    </div>
    <div class="flex items-center gap-2">
        @can('unit-create')
        <a href="{{ route('unit.create') }}" class="h-9 px-4 rounded-md text-sm font-medium whitespace-nowrap inline-flex items-center" style="background-color: var(--erp-primary); color: var(--erp-primary-fg);">
            <i class="fa-solid fa-plus mr-1.5 text-xs"></i> {{ __('message.common.addNew') }}
        </a>
        @endcan
    </div>
</div>

{{-- DataTable Card --}}
<div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
    <div class="p-4 overflow-x-auto">
        <table id="table" class="display responsive nowrap w-full">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('unit::message.unit') }}</th>
                    <th>{{ __('unit::message.unit_value') }}</th>
                    <th>{{ __('message.common.action') }}</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- Delete Modal — included globally in app-tw.blade.php, do NOT include here --}}
@endsection

@section('pagescript')
<script type="application/javascript">
    'use strict';
    window.URL_ROUTE = "{{ route('unit.index') }}";

    window.validationMessages = {};

    var table = '';
    $(function() {
        table = initErpTable('#table', {
            ajax: window.URL_ROUTE,
            processing: true,
            serverSide: true,
            scrollX: true,
            aLengthMenu: [
                [15, 30, 50, 100],
                [15, 30, 50, 100]
            ],
            order: [[0, 'desc']],
            columns: [
                {
                    data: 'id',
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    },
                    orderable: false,
                    width: '50px'
                },
                { data: 'name', name: 'name' },
                { data: 'unit_value', name: 'unit_value' },
                { data: 'action', name: 'action', orderable: false, sortable: false, width: '160px' }
            ]
        });
    });

    // ── View button handler (show modal via erpModal) ──
    $(document).on('click', '.view', function(e) {
        e.preventDefault();
        var id = $(this).attr('data-id');
        if (id != '') {
            var route = "{{ route('unit.show', ':id') }}".replace(':id', id);
            $.ajax({
                type: "GET",
                url: route,
                dataType: 'json',
                success: function(response) {
                    if (response.html) {
                        erpModal({
                            content: response.html,
                            size: 'md'
                        });
                    } else if (response.status_code == 403) {
                        erpToast({ title: 'Warning', message: response.message, type: 'warning' });
                    }
                }
            });
        }
    });
</script>
@endsection
