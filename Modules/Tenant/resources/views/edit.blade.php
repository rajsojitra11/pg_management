@extends('layouts.app-tw')
@section('title', __('tenant::message.edit'))
@section('nav-module', 'tenant')
@section('breadcrumb', __('tenant::message.edit'))

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-zinc-900">{{ __('tenant::message.edit') }}</h1>
        <p class="text-sm text-zinc-500 mt-1">{{ __('tenant::message.edit_tenant') }}</p>
    </div>
    @can('tenant-list')
    <a href="{{ route('tenant.index') }}" class="h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center w-fit">
        <i class="fa-solid fa-arrow-left mr-1.5 text-xs"></i> {{ __('message.common.back') }}
    </a>
    @endcan
</div>

<form action="{{ route('tenant.update', $tenant->public_id ?: $tenant->id) }}" method="POST" id="tenantForm" enctype="multipart/form-data" novalidate class="w-full">
    @csrf
    @method('PUT')

    <div class="rounded-lg border border-zinc-200 bg-white shadow-sm overflow-hidden w-full">

        {{-- Wizard Header --}}
        <div class="flex items-center gap-2 px-4 py-1.5 border-b" style="background:#3D52A0; border-bottom-color:#324690;">
            <div class="h-6 w-6 rounded flex items-center justify-center" style="background:rgba(255,255,255,.18);">
                <i class="fa-solid fa-pen text-white" style="font-size:11px;"></i>
            </div>
            <h2 class="text-sm font-semibold text-white">{{ __('tenant::message.edit_tenant') }}</h2>
        </div>

        {{-- Wizard Step Indicators --}}
        <div id="tenantWizard" data-wizard>
            <div class="flex items-center justify-center px-6 pt-6 pb-4 w-full">
                <div data-wizard-step class="flex flex-col items-center shrink-0">
                    <div class="wizard-circle h-9 w-9 rounded-full" style="background:#3D52A0;color:white;display:flex;align-items:center;justify-content:center;font-size:0.875rem;font-weight:500;">1</div>
                    <div class="wizard-label text-xs font-medium mt-1.5 whitespace-nowrap" style="color:#3D52A0;">{{ __('tenant::message.step_pg_personal') }}</div>
                </div>
                <div class="wizard-line h-0.5 flex-1 mx-4" style="background:#3D52A0;"></div>
                <div data-wizard-step class="flex flex-col items-center shrink-0">
                    <div class="wizard-circle h-9 w-9 rounded-full bg-zinc-100 text-zinc-400 flex items-center justify-center text-sm font-medium border border-zinc-200">2</div>
                    <div class="wizard-label text-xs font-medium mt-1.5 whitespace-nowrap text-zinc-400">{{ __('tenant::message.step_stay_payment') }}</div>
                </div>
                <div class="wizard-line h-0.5 flex-1 mx-4" style="background:#e4e4e7;"></div>
                <div data-wizard-step class="flex flex-col items-center shrink-0">
                    <div class="wizard-circle h-9 w-9 rounded-full bg-zinc-100 text-zinc-400 flex items-center justify-center text-sm font-medium border border-zinc-200">3</div>
                    <div class="wizard-label text-xs font-medium mt-1.5 whitespace-nowrap text-zinc-400">{{ __('tenant::message.step_emergency_address') }}</div>
                </div>
                <div class="wizard-line h-0.5 flex-1 mx-4" style="background:#e4e4e7;"></div>
                <div data-wizard-step class="flex flex-col items-center shrink-0">
                    <div class="wizard-circle h-9 w-9 rounded-full bg-zinc-100 text-zinc-400 flex items-center justify-center text-sm font-medium border border-zinc-200">4</div>
                    <div class="wizard-label text-xs font-medium mt-1.5 whitespace-nowrap text-zinc-400">{{ __('tenant::message.step_review') }}</div>
                </div>
            </div>

            {{-- Server-side error banner --}}
            <div class="erp-form-error-banner rounded-lg border border-red-200 bg-red-50 p-3 mb-4 mx-6" style="display:none;">
                <div class="flex items-start gap-2">
                    <i class="fa-solid fa-circle-exclamation text-red-500 mt-0.5"></i>
                    <p class="text-sm text-red-700 erp-form-error-text"></p>
                </div>
            </div>

            <div class="p-6">

                {{-- Step 1: PG Assignment & Personal Details --}}
                <div data-wizard-panel class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.pg') }} <span class="text-red-500">*</span></label>
                            <select name="pg_id" id="pg_id" required
                                    data-placeholder="— {{ __('message.common.select') }} —"
                                    class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                                <option value=""></option>
                                @foreach ($pgList as $pg)
                                <option value="{{ $pg->id }}" {{ $tenant->pg_id == $pg->id ? 'selected' : '' }}>{{ $pg->pg_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.room') }} <span class="text-red-500">*</span></label>
                            <select name="room_id" id="room_id" required
                                    data-selected="{{ $tenant->room_id }}"
                                    data-placeholder="— {{ __('message.common.select') }} —"
                                    class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                                <option value=""></option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.bed_no') }} <span class="text-red-500">*</span></label>
                            <select name="bed_no" id="bed_no" required
                                    data-placeholder="— {{ __('message.common.select') }} —"
                                    class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                                <option value=""></option>
                            </select>
                            <div class="mt-1 text-xs text-red-500 erp-field-error" id="error_bed_no"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.name_prefix') }}</label>
                            <select name="name_prefix" id="name_prefix"
                                    class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                                <option value="Mr." {{ $tenant->user?->name_prefix == 'Mr.' ? 'selected' : '' }}>Mr.</option>
                                <option value="Mrs." {{ $tenant->user?->name_prefix == 'Mrs.' ? 'selected' : '' }}>Mrs.</option>
                                <option value="Ms." {{ $tenant->user?->name_prefix == 'Ms.' ? 'selected' : '' }}>Ms.</option>
                                <option value="Dr." {{ $tenant->user?->name_prefix == 'Dr.' ? 'selected' : '' }}>Dr.</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.firstname') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="firstname" id="firstname" required
                                   value="{{ $tenant->user?->profile?->firstname ?? old('firstname') }}"
                                   class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                                   placeholder="{{ __('tenant::message.enter_firstname') }}">
                            <div class="mt-1 text-xs text-red-500 erp-field-error" id="error_firstname"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.lastname') }}</label>
                            <input type="text" name="lastname" id="lastname"
                                   value="{{ $tenant->user?->profile?->lastname ?? old('lastname') }}"
                                   class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                                   placeholder="{{ __('tenant::message.enter_lastname') }}">
                            <div class="mt-1 text-xs text-red-500 erp-field-error" id="error_lastname"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.email') }} <span class="text-red-500">*</span></label>
                            <input type="email" name="email" id="email" required
                                   value="{{ $tenant->email ?? old('email') }}"
                                   class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                                   placeholder="{{ __('tenant::message.enter_email') }}" readonly>
                            <div class="mt-1 text-xs text-red-500 erp-field-error" id="error_email"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.mobile') }} <span class="text-red-500">*</span></label>
                            <input type="tel" name="mobile" id="mobile" required maxlength="15"
                                   value="{{ $tenant->phone ?? old('phone') }}"
                                   class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                                   placeholder="{{ __('tenant::message.enter_mobile') }}">
                            <div class="mt-1 text-xs text-red-500 erp-field-error" id="error_mobile"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.date_of_birth') }}</label>
                            <input type="text" name="date_of_birth" id="date_of_birth"
                                   value="{{ $tenant->formatted_date_of_birth ?? old('date_of_birth') }}"
                                   class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm flatpickr-date focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                                   placeholder="DD-MM-YYYY" autocomplete="off">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.gender') }}</label>
                            <select name="gender" id="gender"
                                    class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                                <option value="">{{ __('message.common.select') }}</option>
                                <option value="Male" {{ ($tenant->gender ?? '') == 'Male' ? 'selected' : '' }}>{{ __('tenant::message.male') }}</option>
                                <option value="Female" {{ ($tenant->gender ?? '') == 'Female' ? 'selected' : '' }}>{{ __('tenant::message.female') }}</option>
                                <option value="Other" {{ ($tenant->gender ?? '') == 'Other' ? 'selected' : '' }}>{{ __('tenant::message.other') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.occupation') }}</label>
                            <input type="text" name="occupation" id="occupation"
                                   value="{{ $tenant->occupation ?? old('occupation') }}"
                                   class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                                   placeholder="{{ __('tenant::message.enter_occupation') }}">
                        </div>
                    </div>
                </div>

                {{-- Step 2: Stay & Payment Details --}}
                <div data-wizard-panel style="display:none;" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.checkin_date') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="checkin_date" id="checkin_date"
                                   value="{{ $tenant->formatted_checkin_date ?? old('checkin_date') }}"
                                   class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm flatpickr-date focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                                   placeholder="DD-MM-YYYY" autocomplete="off">
                            <div class="mt-1 text-xs text-red-500 erp-field-error" id="error_checkin_date"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.expected_checkout_date') }}</label>
                            <input type="text" name="expected_checkout_date" id="expected_checkout_date"
                                   value="{{ $tenant->formatted_expected_checkout_date ?? old('expected_checkout_date') }}"
                                   class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm flatpickr-date focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                                   placeholder="DD-MM-YYYY" autocomplete="off">
                            <div class="mt-1 text-xs text-red-500 erp-field-error" id="error_expected_checkout_date"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.monthly_rent') }}</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-sm">₹</span>
                                <input type="number" name="monthly_rent" id="monthly_rent" min="0" step="0.01"
                                       value="{{ $tenant->monthly_rent ?? old('monthly_rent') }}"
                                       class="h-9 w-full rounded-md border border-zinc-200 bg-transparent pl-7 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                                       placeholder="0.00">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.security_deposit') }} <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-sm">₹</span>
                                <input type="number" name="security_deposit" id="security_deposit" min="0" step="0.01" required
                                       value="{{ $tenant->security_deposit ?? old('security_deposit') }}"
                                       class="h-9 w-full rounded-md border border-zinc-200 bg-transparent pl-7 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                                       placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.payment_method') }}</label>
                            <select name="payment_method" id="payment_method"
                                    class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                                <option value="">{{ __('message.common.select') }}</option>
                                <option value="Cash" {{ $tenant->payment_method == 'Cash' ? 'selected' : '' }}>{{ __('tenant::message.cash') }}</option>
                                <option value="Bank Transfer" {{ $tenant->payment_method == 'Bank Transfer' ? 'selected' : '' }}>{{ __('tenant::message.bank_transfer') }}</option>
                                <option value="Cheque" {{ $tenant->payment_method == 'Cheque' ? 'selected' : '' }}>{{ __('tenant::message.cheque') }}</option>
                                <option value="UPI" {{ $tenant->payment_method == 'UPI' ? 'selected' : '' }}>{{ __('tenant::message.upi') }}</option>
                                <option value="Other" {{ $tenant->payment_method == 'Other' ? 'selected' : '' }}>{{ __('tenant::message.other') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.id_proof_type') }} <span class="text-red-500">*</span></label>
                            <select name="id_proof_type" id="id_proof_type" required
                                    class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                                <option value="">{{ __('message.common.select') }}</option>
                                <option value="Aadhar Card" {{ $tenant->id_proof_type == 'Aadhar Card' ? 'selected' : '' }}>{{ __('tenant::message.aadhar_card') }}</option>
                                <option value="PAN Card" {{ $tenant->id_proof_type == 'PAN Card' ? 'selected' : '' }}>{{ __('tenant::message.pan_card') }}</option>
                                <option value="Passport" {{ $tenant->id_proof_type == 'Passport' ? 'selected' : '' }}>{{ __('tenant::message.passport') }}</option>
                                <option value="Driving License" {{ $tenant->id_proof_type == 'Driving License' ? 'selected' : '' }}>{{ __('tenant::message.driving_license') }}</option>
                                <option value="Voter ID" {{ $tenant->id_proof_type == 'Voter ID' ? 'selected' : '' }}>{{ __('tenant::message.voter_id') }}</option>
                                <option value="Other" {{ $tenant->id_proof_type == 'Other' ? 'selected' : '' }}>{{ __('tenant::message.other') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.id_proof_number') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="id_proof_number" id="id_proof_number" required
                                   value="{{ $tenant->id_proof_number ?? old('id_proof_number') }}"
                                   class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                                   placeholder="{{ __('tenant::message.enter_id_proof_number') }}">
                            <div class="mt-1 text-xs text-red-500 erp-field-error" id="error_id_proof_number"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.id_proof_file') }}</label>
                            <input type="file" name="id_proof_file" id="id_proof_file" accept=".jpg,.jpeg,.png,.pdf"
                                   class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                            <div class="mt-1 text-xs text-red-500 erp-field-error" id="error_id_proof_file"></div>
                            @if ($tenant->id_proof_file)
                                <p class="mt-1 text-xs text-zinc-500">
                                    <i class="fa-solid fa-paperclip mr-1"></i>
                                    <a href="{{ Storage::url($tenant->id_proof_file) }}" target="_blank" class="text-blue-600 hover:underline">{{ basename($tenant->id_proof_file) }}</a>
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Step 3: Emergency Contact & Permanent Address --}}
                <div data-wizard-panel style="display:none;" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.emergency_contact_name') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="emergency_contact_name" id="emergency_contact_name" required
                                   value="{{ $tenant->emergency_contact_name ?? old('emergency_contact_name') }}"
                                   class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                                   placeholder="{{ __('tenant::message.enter_emergency_contact_name') }}">
                            <div class="mt-1 text-xs text-red-500 erp-field-error" id="error_emergency_contact_name"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.emergency_relation') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="emergency_relation" id="emergency_relation" required
                                   value="{{ $tenant->emergency_relation ?? old('emergency_relation') }}"
                                   class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                                   placeholder="{{ __('tenant::message.enter_emergency_relation') }}">
                            <div class="mt-1 text-xs text-red-500 erp-field-error" id="error_emergency_relation"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.emergency_contact_number') }} <span class="text-red-500">*</span></label>
                            <input type="tel" name="emergency_contact_number" id="emergency_contact_number" maxlength="15" required
                                   value="{{ $tenant->emergency_contact_number ?? old('emergency_contact_number') }}"
                                   class="h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                                   placeholder="{{ __('tenant::message.enter_emergency_contact_number') }}">
                            <div class="mt-1 text-xs text-red-500 erp-field-error" id="error_emergency_contact_number"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.permanent_state') }}</label>
                            <select name="permanent_state_id" id="permanent_state_id"
                                    data-fresh-prefetch="{{ route('lookup.states') }}"
                                    data-selected="{{ $tenant->permanent_state_id }}"
                                    data-placeholder="— {{ __('message.common.select') }} —"
                                    class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                                <option value=""></option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.permanent_city') }}</label>
                            <select name="permanent_city_id" id="permanent_city_id"
                                    data-selected="{{ $tenant->permanent_city_id }}"
                                    data-placeholder="— {{ __('message.common.select') }} —"
                                    class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                                <option value=""></option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.permanent_address') }}</label>
                        <textarea name="permanent_address" id="permanent_address" rows="3"
                                  class="w-full rounded-md border border-zinc-200 bg-transparent px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                                  placeholder="{{ __('tenant::message.enter_permanent_address') }}">{{ $tenant->permanent_address ?? old('permanent_address') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.additional_notes') }}</label>
                        <textarea name="additional_notes" id="additional_notes" rows="2"
                                  class="w-full rounded-md border border-zinc-200 bg-transparent px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2"
                                  placeholder="{{ __('tenant::message.enter_additional_notes') }}">{{ $tenant->additional_notes ?? old('additional_notes') }}</textarea>
                    </div>
                </div>

                {{-- Step 4: Review & Submit --}}
                <div data-wizard-panel style="display:none;" class="space-y-6">
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 space-y-4">
                        <h4 class="text-sm font-semibold text-zinc-900">{{ __('tenant::message.review_info') }}</h4>

                        <div>
                            <h5 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">{{ __('tenant::message.section_pg_personal') }}</h5>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                                <div><span class="text-zinc-500">{{ __('tenant::message.pg') }}:</span> <span class="text-zinc-900 font-medium review-pg">-</span></div>
                                <div><span class="text-zinc-500">{{ __('tenant::message.room') }}:</span> <span class="text-zinc-900 font-medium review-room">-</span></div>
                                <div><span class="text-zinc-500">{{ __('tenant::message.bed_no') }}:</span> <span class="text-zinc-900 font-medium review-bed-no">-</span></div>
                                <div><span class="text-zinc-500">{{ __('tenant::message.name_prefix') }}:</span> <span class="text-zinc-900 font-medium review-name-prefix">-</span></div>
                                <div><span class="text-zinc-500">{{ __('tenant::message.firstname') }}:</span> <span class="text-zinc-900 font-medium review-firstname">-</span></div>
                                <div><span class="text-zinc-500">{{ __('tenant::message.lastname') }}:</span> <span class="text-zinc-900 font-medium review-lastname">-</span></div>
                                <div><span class="text-zinc-500">{{ __('tenant::message.email') }}:</span> <span class="text-zinc-900 font-medium review-email">-</span></div>
                                <div><span class="text-zinc-500">{{ __('tenant::message.mobile') }}:</span> <span class="text-zinc-900 font-medium review-mobile">-</span></div>
                                <div><span class="text-zinc-500">{{ __('tenant::message.date_of_birth') }}:</span> <span class="text-zinc-900 font-medium review-dob">-</span></div>
                                <div><span class="text-zinc-500">{{ __('tenant::message.gender') }}:</span> <span class="text-zinc-900 font-medium review-gender">-</span></div>
                                <div><span class="text-zinc-500">{{ __('tenant::message.occupation') }}:</span> <span class="text-zinc-900 font-medium review-occupation">-</span></div>
                            </div>
                        </div>

                        <div>
                            <h5 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">{{ __('tenant::message.section_stay_payment') }}</h5>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                                <div><span class="text-zinc-500">{{ __('tenant::message.checkin_date') }}:</span> <span class="text-zinc-900 font-medium review-checkin">-</span></div>
                                <div><span class="text-zinc-500">{{ __('tenant::message.expected_checkout_date') }}:</span> <span class="text-zinc-900 font-medium review-checkout">-</span></div>
                                <div><span class="text-zinc-500">{{ __('tenant::message.monthly_rent') }}:</span> <span class="text-zinc-900 font-medium review-rent">-</span></div>
                                <div><span class="text-zinc-500">{{ __('tenant::message.security_deposit') }}:</span> <span class="text-zinc-900 font-medium review-deposit">-</span></div>
                                <div><span class="text-zinc-500">{{ __('tenant::message.payment_method') }}:</span> <span class="text-zinc-900 font-medium review-payment-method">-</span></div>
                                <div><span class="text-zinc-500">{{ __('tenant::message.id_proof_type') }}:</span> <span class="text-zinc-900 font-medium review-id-proof-type">-</span></div>
                                <div><span class="text-zinc-500">{{ __('tenant::message.id_proof_number') }}:</span> <span class="text-zinc-900 font-medium review-id-proof-number">-</span></div>
                                <div><span class="text-zinc-500">{{ __('tenant::message.id_proof_file') }}:</span> <span class="text-zinc-900 font-medium review-id-proof-file">-</span></div>
                            </div>
                        </div>

                        <div>
                            <h5 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">{{ __('tenant::message.section_emergency_address') }}</h5>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                                <div><span class="text-zinc-500">{{ __('tenant::message.emergency_contact_name') }}:</span> <span class="text-zinc-900 font-medium review-emergency-name">-</span></div>
                                <div><span class="text-zinc-500">{{ __('tenant::message.emergency_relation') }}:</span> <span class="text-zinc-900 font-medium review-emergency-relation">-</span></div>
                                <div><span class="text-zinc-500">{{ __('tenant::message.emergency_contact_number') }}:</span> <span class="text-zinc-900 font-medium review-emergency-phone">-</span></div>
                                <div><span class="text-zinc-500">{{ __('tenant::message.permanent_state') }}:</span> <span class="text-zinc-900 font-medium review-perm-state">-</span></div>
                                <div><span class="text-zinc-500">{{ __('tenant::message.permanent_city') }}:</span> <span class="text-zinc-900 font-medium review-perm-city">-</span></div>
                                <div class="md:col-span-3"><span class="text-zinc-500">{{ __('tenant::message.permanent_address') }}:</span> <span class="text-zinc-900 font-medium review-perm-address">-</span></div>
                                <div class="md:col-span-3"><span class="text-zinc-500">{{ __('tenant::message.additional_notes') }}:</span> <span class="text-zinc-900 font-medium review-notes">-</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 mb-1.5">{{ __('tenant::message.status') }} <span class="text-red-500">*</span></label>
                            <select name="status" id="status" required
                                    class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                                <option value="Active" {{ $tenant->status == 'active' || $tenant->status == 'Active' ? 'selected' : '' }}>{{ __('message.common.active') }}</option>
                                <option value="Inactive" {{ $tenant->status == 'inactive' || $tenant->status == 'Inactive' ? 'selected' : '' }}>{{ __('message.common.inactive') }}</option>
                            </select>
                            <div class="mt-1 text-xs text-red-500 erp-field-error" id="error_status"></div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Wizard Navigation --}}
            <div class="flex items-center justify-between gap-2 p-6 pt-0">
                <button type="button" data-wizard-prev
                        class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center">
                    <i class="fa-solid fa-arrow-left mr-1.5 text-xs"></i> {{ __('tenant::message.back') }}
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" data-wizard-next
                            class="h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 inline-flex items-center next-btn">
                        {{ __('tenant::message.next') }} <i class="fa-solid fa-arrow-right ml-1.5 text-xs"></i>
                    </button>
                    <button type="button" id="printReview"
                            class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 inline-flex items-center print-btn" style="display:none;">
                        <i class="fa-solid fa-print mr-1.5 text-xs"></i> {{ __('tenant::message.print') }}
                    </button>
                    <button type="submit" id="save"
                            class="h-9 px-6 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 inline-flex items-center submit-btn" style="display:none;">
                        <i class="fa-solid fa-check mr-1.5 text-xs"></i> {{ __('message.common.update') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- Print Template (hidden, shown during print) --}}
<div id="printTemplate" style="display:none;">
    <div class="print-page">
        <div class="print-header">
            <div class="print-header-icon"></div>
            <div class="print-header-text">
                <h1>{{ __('tenant::message.tenant_registration') }}</h1>
                <p>{{ __('tenant::message.review_info') }}</p>
            </div>
        </div>

        <table class="print-table">
            <tr class="section-head"><td colspan="4">{{ __('tenant::message.section_pg_personal') }}</td></tr>
            <tr>
                <td class="label">{{ __('tenant::message.pg') }}</td><td class="value print-pg">-</td>
                <td class="label">{{ __('tenant::message.room') }}</td><td class="value print-room">-</td>
            </tr>
            <tr>
                <td class="label">{{ __('tenant::message.bed_no') }}</td><td class="value print-bed-no">-</td>
                <td class="label">{{ __('tenant::message.name_prefix') }}</td><td class="value print-name-prefix">-</td>
            </tr>
            <tr>
                <td class="label">{{ __('tenant::message.firstname') }}</td><td class="value print-firstname">-</td>
                <td class="label">{{ __('tenant::message.lastname') }}</td><td class="value print-lastname">-</td>
            </tr>
            <tr>
                <td class="label">{{ __('tenant::message.email') }}</td><td class="value print-email">-</td>
                <td class="label">{{ __('tenant::message.mobile') }}</td><td class="value print-mobile">-</td>
            </tr>
            <tr>
                <td class="label">{{ __('tenant::message.date_of_birth') }}</td><td class="value print-dob">-</td>
                <td class="label">{{ __('tenant::message.gender') }}</td><td class="value print-gender">-</td>
            </tr>
            <tr>
                <td class="label">{{ __('tenant::message.occupation') }}</td><td class="value print-occupation" colspan="3">-</td>
            </tr>
        </table>

        <table class="print-table">
            <tr class="section-head"><td colspan="4">{{ __('tenant::message.section_stay_payment') }}</td></tr>
            <tr>
                <td class="label">{{ __('tenant::message.checkin_date') }}</td><td class="value print-checkin">-</td>
                <td class="label">{{ __('tenant::message.expected_checkout_date') }}</td><td class="value print-checkout">-</td>
            </tr>
            <tr>
                <td class="label">{{ __('tenant::message.monthly_rent') }}</td><td class="value print-rent">-</td>
                <td class="label">{{ __('tenant::message.security_deposit') }}</td><td class="value print-deposit">-</td>
            </tr>
            <tr>
                <td class="label">{{ __('tenant::message.payment_method') }}</td><td class="value print-payment-method">-</td>
                <td class="label">{{ __('tenant::message.id_proof_type') }}</td><td class="value print-id-proof-type">-</td>
            </tr>
            <tr>
                <td class="label">{{ __('tenant::message.id_proof_number') }}</td><td class="value print-id-proof-number" colspan="3">-</td>
            </tr>
        </table>

        <table class="print-table">
            <tr class="section-head"><td colspan="4">{{ __('tenant::message.section_emergency_address') }}</td></tr>
            <tr>
                <td class="label">{{ __('tenant::message.emergency_contact_name') }}</td><td class="value print-emergency-name">-</td>
                <td class="label">{{ __('tenant::message.emergency_relation') }}</td><td class="value print-emergency-relation">-</td>
            </tr>
            <tr>
                <td class="label">{{ __('tenant::message.emergency_contact_number') }}</td><td class="value print-emergency-phone" colspan="3">-</td>
            </tr>
            <tr>
                <td class="label">{{ __('tenant::message.permanent_state') }}</td><td class="value print-perm-state">-</td>
                <td class="label">{{ __('tenant::message.permanent_city') }}</td><td class="value print-perm-city">-</td>
            </tr>
            <tr>
                <td class="label">{{ __('tenant::message.permanent_address') }}</td><td class="value print-perm-address" colspan="3">-</td>
            </tr>
            <tr>
                <td class="label">{{ __('tenant::message.additional_notes') }}</td><td class="value print-notes" colspan="3">-</td>
            </tr>
        </table>

        <div class="print-footer">
            {{ __('tenant::message.print_footer') }}
        </div>
    </div>
</div>

<style>
@media print {
    @page { margin: 12mm 15mm; size: A4; }
    body * { visibility: hidden; }
    #printTemplate, #printTemplate * { visibility: visible; }
    #printTemplate { display: block !important; position: absolute; left: 0; top: 0; width: 100%; }
}
.print-page {
    font-family: 'Segoe UI', Arial, sans-serif;
    max-width: 750px; margin: 0 auto; color: #1a1a1a;
}
.print-header {
    display: flex; align-items: center; gap: 14px;
    border-bottom: 3px solid #3D52A0; padding-bottom: 12px; margin-bottom: 16px;
}
.print-header-icon {
    width: 36px; height: 36px; border-radius: 8px;
    background: #3D52A0; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.print-header-text h1 {
    font-size: 18px; margin: 0; color: #3D52A0; font-weight: 700;
}
.print-header-text p {
    font-size: 11px; margin: 2px 0 0; color: #666;
}
.print-table {
    width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 10px;
}
.section-head td {
    background: #3D52A0; color: #fff; font-weight: 600; font-size: 12px;
    padding: 6px 10px; letter-spacing: 0.3px;
}
.print-table td.label {
    width: 22%; padding: 5px 10px; border: 1px solid #e0e0e0;
    font-weight: 600; color: #555; background: #f8f9fc; font-size: 10.5px;
}
.print-table td.value {
    width: 28%; padding: 5px 10px; border: 1px solid #e0e0e0; color: #1a1a1a;
    font-weight: 500; font-size: 10.5px;
}
.print-footer {
    text-align: center; margin-top: 16px; padding-top: 10px;
    border-top: 1px solid #ddd; font-size: 9px; color: #999;
}
</style>
@endsection

@section('pagescript')
<script>
$(document).ready(function() {
    var wizard = null;
    var check = setInterval(function() {
        if (typeof erpWizard === 'function' && document.getElementById('tenantWizard')) {
            clearInterval(check);
            wizard = erpWizard('tenantWizard');

            var steps = document.querySelectorAll('[data-wizard-step]');
            var BLUE = '#3D52A0';

            function refreshStepUI(index) {
                var allLines = document.querySelectorAll('#tenantWizard .wizard-line');
                steps.forEach(function (step, i) {
                    var circle = step.querySelector('.wizard-circle');
                    var label = step.querySelector('.wizard-label');

                    if (i < index) {
                        if (circle) {
                            circle.style.background = BLUE;
                            circle.style.color = 'white';
                            circle.style.border = 'none';
                            circle.innerHTML = '<i class="fa-solid fa-check text-xs"></i>';
                        }
                        if (label) { label.style.color = BLUE; }
                        step.classList.add('completed');
                    } else if (i === index) {
                        if (circle) {
                            circle.style.background = BLUE;
                            circle.style.color = 'white';
                            circle.style.border = 'none';
                            circle.textContent = '' + (i + 1);
                        }
                        if (label) { label.style.color = BLUE; }
                        step.classList.remove('completed');
                    } else {
                        if (circle) {
                            circle.style.background = '#f4f4f5';
                            circle.style.color = '#a1a1aa';
                            circle.style.border = '1px solid #e4e4e7';
                            circle.textContent = '' + (i + 1);
                        }
                        if (label) { label.style.color = '#a1a1aa'; }
                        step.classList.remove('completed');
                    }
                });
                allLines.forEach(function (line, j) {
                    line.style.background = j < index ? BLUE : '#e4e4e7';
                });

                var panels = document.querySelectorAll('[data-wizard-panel]');
                panels.forEach(function (panel, i) {
                    panel.style.display = (i !== index) ? 'none' : '';
                });

                var _nextBtn = document.querySelector('[data-wizard-next]');
                var _submitBtn = document.querySelector('.submit-btn');
                var _printBtn = document.querySelector('.print-btn');
                if (index === steps.length - 1) {
                    if (_nextBtn) _nextBtn.style.display = 'none';
                    if (_submitBtn) _submitBtn.style.display = 'inline-flex';
                    if (_printBtn) _printBtn.style.display = 'inline-flex';
                } else {
                    if (_nextBtn) _nextBtn.style.display = 'inline-flex';
                    if (_submitBtn) _submitBtn.style.display = 'none';
                    if (_printBtn) _printBtn.style.display = 'none';
                }

                if (index === 3) {
                    updateReviewSection();
                }
            }

            wizard.goTo = function(index) {
                refreshStepUI(index);
            };

            document.querySelectorAll('[data-wizard-next]').forEach(function (btn) {
                btn.replaceWith(btn.cloneNode(true));
            });
            document.querySelectorAll('[data-wizard-prev]').forEach(function (btn) {
                btn.replaceWith(btn.cloneNode(true));
            });

            var currentStep = 0;
            document.querySelector('[data-wizard-next]')?.addEventListener('click', function () {
                if (currentStep < steps.length - 1) {
                    currentStep++;
                    refreshStepUI(currentStep);
                }
            });
            document.querySelector('[data-wizard-prev]')?.addEventListener('click', function () {
                if (currentStep > 0) {
                    currentStep--;
                    refreshStepUI(currentStep);
                }
            });

            refreshStepUI(0);
        }
    }, 100);

    function updateReviewSection() {
        document.querySelector('.review-name-prefix').textContent = document.getElementById('name_prefix').value || '-';

        var pgEl = document.getElementById('pg_id');
        var pgText = pgEl ? (pgEl.options[pgEl.selectedIndex] ? pgEl.options[pgEl.selectedIndex].text : '-') : '-';
        document.querySelector('.review-pg').textContent = pgText;

        var roomEl = document.getElementById('room_id');
        var roomText = roomEl ? (roomEl.options[roomEl.selectedIndex] ? roomEl.options[roomEl.selectedIndex].text : '-') : '-';
        document.querySelector('.review-room').textContent = roomText;

        var bedEl = document.getElementById('bed_no');
        var bedText = bedEl ? (bedEl.options[bedEl.selectedIndex] ? bedEl.options[bedEl.selectedIndex].text : '-') : '-';
        document.querySelector('.review-bed-no').textContent = bedText;
        document.querySelector('.review-firstname').textContent = document.getElementById('firstname').value || '-';
        document.querySelector('.review-lastname').textContent = document.getElementById('lastname').value || '-';
        document.querySelector('.review-email').textContent = document.getElementById('email').value || '-';
        document.querySelector('.review-mobile').textContent = document.getElementById('mobile').value || '-';
        document.querySelector('.review-dob').textContent = document.getElementById('date_of_birth').value || '-';

        var genderEl = document.getElementById('gender');
        document.querySelector('.review-gender').textContent = genderEl ? (genderEl.options[genderEl.selectedIndex] ? genderEl.options[genderEl.selectedIndex].text : '-') : '-';

        document.querySelector('.review-occupation').textContent = document.getElementById('occupation').value || '-';
        document.querySelector('.review-checkin').textContent = document.getElementById('checkin_date').value || '-';
        document.querySelector('.review-checkout').textContent = document.getElementById('expected_checkout_date').value || '-';
        document.querySelector('.review-rent').textContent = document.getElementById('monthly_rent').value ? '₹' + document.getElementById('monthly_rent').value : '-';
        document.querySelector('.review-deposit').textContent = document.getElementById('security_deposit').value ? '₹' + document.getElementById('security_deposit').value : '-';

        var pmEl = document.getElementById('payment_method');
        document.querySelector('.review-payment-method').textContent = pmEl ? (pmEl.options[pmEl.selectedIndex] ? pmEl.options[pmEl.selectedIndex].text : '-') : '-';

        var idptEl = document.getElementById('id_proof_type');
        document.querySelector('.review-id-proof-type').textContent = idptEl ? (idptEl.options[idptEl.selectedIndex] ? idptEl.options[idptEl.selectedIndex].text : '-') : '-';

        document.querySelector('.review-id-proof-number').textContent = document.getElementById('id_proof_number').value || '-';

        var idFile = document.getElementById('id_proof_file');
        document.querySelector('.review-id-proof-file').textContent = idFile && idFile.files && idFile.files.length > 0 ? idFile.files[0].name : '{{ $tenant->id_proof_file ? basename($tenant->id_proof_file) : "-" }}';

        document.querySelector('.review-emergency-name').textContent = document.getElementById('emergency_contact_name').value || '-';
        document.querySelector('.review-emergency-relation').textContent = document.getElementById('emergency_relation').value || '-';
        document.querySelector('.review-emergency-phone').textContent = document.getElementById('emergency_contact_number').value || '-';

        var stateEl = document.getElementById('permanent_state_id');
        var stateText = stateEl ? (stateEl.options[stateEl.selectedIndex] ? stateEl.options[stateEl.selectedIndex].text : '-') : '-';
        document.querySelector('.review-perm-state').textContent = stateText;

        var cityEl = document.getElementById('permanent_city_id');
        var cityText = cityEl ? (cityEl.options[cityEl.selectedIndex] ? cityEl.options[cityEl.selectedIndex].text : '-') : '-';
        document.querySelector('.review-perm-city').textContent = cityText;

        document.querySelector('.review-perm-address').textContent = document.getElementById('permanent_address').value || '-';
        document.querySelector('.review-notes').textContent = document.getElementById('additional_notes').value || '-';
    }

    // Initialize PG select
    (function() {
        var $pg = $('#pg_id');
        if ($pg.length && !$pg.next('.erp-select-wrapper').length) {
            initErpSelect('#pg_id', { placeholder: '— {{ __("message.common.select") }} —' });
        }
    })();

    // PG → Room cascade
    (function() {
        var $pg = $('#pg_id');
        var $room = $('#room_id');
        if (!$pg.length || !$room.length) return;

        var roomInst = null;
        var bedInst = null;
        var _roomsCache = [];
        var _selectedRoom = '{{ $tenant->room_id }}';
        var _selectedBed = '{{ $tenant->bed_no }}';

        function populateBeds(roomVal) {
            if (!roomVal) {
                if (bedInst) { bedInst.setOptions([]); bedInst.setValue(''); }
                return;
            }
            var room = _roomsCache.find(function(r) { return r.value == roomVal; });
            var capacity = room ? (parseInt(room.bed_capacity) || 0) : 0;
            var beds = [];
            for (var i = 0; i < capacity; i++) {
                var letter = String.fromCharCode(65 + i);
                beds.push({ value: letter, label: 'Bed ' + letter });
            }
            if (!bedInst) {
                bedInst = erpSearchSelect('#bed_no', { placeholder: '— {{ __("message.common.select") }} —' });
            }
            bedInst.setOptions(beds);
            if (_selectedBed) {
                bedInst.setValue(_selectedBed);
                _selectedBed = null;
            } else {
                bedInst.setValue('');
            }
        }

        var check = setInterval(function() {
            if ($pg.next('.erp-select-wrapper').length) {
                clearInterval(check);
                $pg.on('change', function() {
                    var val = $(this).val();
                    if (!val) {
                        if (roomInst) { roomInst.setOptions([]); roomInst.setValue(''); }
                        if (bedInst) { bedInst.setOptions([]); bedInst.setValue(''); }
                        _roomsCache = [];
                        return;
                    }
                    $.get('{{ route("lookup.rooms-by-pg") }}', { pg_id: val, limit: 9999 }, function(data) {
                        _roomsCache = data || [];
                        if (!roomInst) {
                            roomInst = erpSearchSelect('#room_id', { placeholder: '— {{ __("message.common.select") }} —' });
                        }
                        roomInst.setOptions(_roomsCache);
                        if (_selectedRoom) {
                            roomInst.setValue(_selectedRoom);
                            populateBeds(_selectedRoom);
                            _selectedRoom = null;
                        } else {
                            roomInst.setValue('');
                            populateBeds('');
                        }
                    });
                });

                $room.on('change', function() {
                    populateBeds($(this).val());
                });

                // Trigger initial load
                if ($pg.val()) {
                    $pg.trigger('change');
                }
            }
        }, 100);
    })();

    // State → City cascade (permanent address)
    (function() {
        var $state = $('#permanent_state_id');
        var $city = $('#permanent_city_id');
        if (!$state.length || !$city.length) return;

        var cityInst = null;
        var selectedCity = '{{ $tenant->permanent_city_id }}';
        var check = setInterval(function() {
            if ($state.next('.erp-select-wrapper').length) {
                clearInterval(check);
                $state.on('change', function() {
                    var val = $(this).val();
                    if (!val) {
                        if (cityInst) { cityInst.setOptions([]); cityInst.setValue(''); }
                        return;
                    }
                    $.get('{{ route("lookup.cities") }}', { state_id: val, limit: 9999 }, function(data) {
                        if (!cityInst) {
                            cityInst = erpSearchSelect('#permanent_city_id', { placeholder: '— {{ __("message.common.select") }} —' });
                        }
                        cityInst.setOptions(data || []);
                        if (selectedCity) {
                            cityInst.setValue(selectedCity);
                            selectedCity = null;
                        } else {
                            cityInst.setValue('');
                        }
                    });
                });
                if ($state.val()) {
                    $state.trigger('change');
                }
            }
        }, 100);
    })();

    // Flatpickr for date fields
    if ($('#date_of_birth').length) {
        flatpickr('#date_of_birth', { dateFormat: 'd-m-Y', maxDate: 'today', allowInput: true });
    }
    if ($('#checkin_date').length) {
        flatpickr('#checkin_date', { dateFormat: 'd-m-Y', allowInput: true });
    }
    if ($('#expected_checkout_date').length) {
        flatpickr('#expected_checkout_date', { dateFormat: 'd-m-Y', allowInput: true });
    }

    // Print review
    $('#printReview').on('click', function() {
        function g(id) { return document.getElementById(id) ? document.getElementById(id).value || '-' : '-'; }
        function selText(id) {
            var el = document.getElementById(id);
            return el && el.options[el.selectedIndex] ? el.options[el.selectedIndex].text : '-';
        }

        document.querySelector('.print-pg').textContent = selText('pg_id');
        document.querySelector('.print-room').textContent = selText('room_id');
        document.querySelector('.print-bed-no').textContent = g('bed_no');
        document.querySelector('.print-name-prefix').textContent = g('name_prefix');
        document.querySelector('.print-firstname').textContent = g('firstname');
        document.querySelector('.print-lastname').textContent = g('lastname');
        document.querySelector('.print-email').textContent = g('email');
        document.querySelector('.print-mobile').textContent = g('mobile');
        document.querySelector('.print-dob').textContent = g('date_of_birth');
        document.querySelector('.print-gender').textContent = selText('gender');
        document.querySelector('.print-occupation').textContent = g('occupation');
        document.querySelector('.print-checkin').textContent = g('checkin_date');
        document.querySelector('.print-checkout').textContent = g('expected_checkout_date');
        document.querySelector('.print-rent').textContent = g('monthly_rent') !== '-' ? '₹' + g('monthly_rent') : '-';
        document.querySelector('.print-deposit').textContent = g('security_deposit') !== '-' ? '₹' + g('security_deposit') : '-';
        document.querySelector('.print-payment-method').textContent = selText('payment_method');
        document.querySelector('.print-id-proof-type').textContent = selText('id_proof_type');
        document.querySelector('.print-id-proof-number').textContent = g('id_proof_number');
        document.querySelector('.print-emergency-name').textContent = g('emergency_contact_name');
        document.querySelector('.print-emergency-relation').textContent = g('emergency_relation');
        document.querySelector('.print-emergency-phone').textContent = g('emergency_contact_number');
        document.querySelector('.print-perm-state').textContent = selText('permanent_state_id');
        document.querySelector('.print-perm-city').textContent = selText('permanent_city_id');
        document.querySelector('.print-perm-address').textContent = g('permanent_address');
        document.querySelector('.print-notes').textContent = g('additional_notes');

        window.print();
    });

    function showInlineServerErrors($form, errors) {
        var firstField = null;
        $.each(errors, function(field, messages) {
            var msg = Array.isArray(messages) ? messages[0] : messages;
            var $field = $form.find('[name="' + field + '"]');
            if (!$field.length) $field = $form.find('[name="' + field + '[]"]');
            if ($field.length) {
                $field.addClass('border-red-500');
                var $errEl = $('#error_' + field);
                if ($errEl.length) {
                    $errEl.text(msg).show();
                }
                if (!firstField) firstField = $field;
            } else {
                showFormError($form, msg);
            }
        });
        if (firstField) {
            firstField[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    $('#tenantForm').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('[type="submit"]');

        var errors = validateFormFields($form);
        if (errors.length > 0) {
            if (typeof setButtonError === 'function') setButtonError($btn);
            return false;
        }

        $form.find('.erp-field-error').text('').hide();
        $form.find('.border-red-500').removeClass('border-red-500');
        $form.find('.erp-form-error-banner').hide();
        if (typeof setButtonLoading === 'function') setButtonLoading($btn);

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
                    if (typeof erpToast === 'function') {
                        erpToast({ title: 'Success', message: response.message, type: 'success' });
                    }
                    setTimeout(function() { location.href = "{{ route('tenant.index') }}"; }, 500);
                } else {
                    if (typeof setButtonError === 'function') setButtonError($btn);
                    if (response.errors) {
                        showInlineServerErrors($form, response.errors);
                    } else {
                        showFormError($form, response.message || 'Something went wrong');
                    }
                }
            },
            error: function(xhr) {
                if (typeof setButtonError === 'function') setButtonError($btn);
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    showInlineServerErrors($form, xhr.responseJSON.errors);
                    if (typeof erpToast === 'function') {
                        erpToast({ title: 'Validation Error', message: 'Please check the form fields', type: 'error' });
                    }
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
