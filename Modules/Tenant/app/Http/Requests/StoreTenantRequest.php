<?php

namespace Modules\Tenant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Step 1: User fields
            'name_prefix' => ['nullable', 'string', 'max:10'],
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email', 'max:255'],
            'mobile' => ['required', 'string', 'max:20', 'unique:users,mobile'],
            'date_of_birth' => ['nullable', 'date_format:d-m-Y'],
            'gender' => ['nullable', 'string', 'in:Male,Female,Other'],
            'occupation' => ['nullable', 'string', 'max:100'],

            // Step 1: PG & Room
            'pg_id' => ['required', 'integer', 'exists:pg_management,id'],
            'room_id' => ['required', 'integer', 'exists:pg_rooms,id'],
            'bed_no' => ['required', 'string', 'max:20'],

            // Step 2: Stay & Payment
            'checkin_date' => ['nullable', 'date_format:d-m-Y'],
            'expected_checkout_date' => ['nullable', 'date_format:d-m-Y', 'after_or_equal:checkin_date'],
            'monthly_rent' => ['nullable', 'numeric', 'min:0'],
            'security_deposit' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'id_proof_type' => ['required', 'string', 'max:50'],
            'id_proof_number' => ['required', 'string', 'max:100'],
            'id_proof_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],

            // Step 3: Emergency & Permanent Address
            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_relation' => ['required', 'string', 'max:100'],
            'emergency_contact_number' => ['required', 'string', 'max:20'],
            'permanent_state_id' => ['nullable', 'integer', 'exists:states,id'],
            'permanent_city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'permanent_address' => ['nullable', 'string'],
            'additional_notes' => ['nullable', 'string'],

            'status' => ['required', 'string', 'in:Active,Inactive'],
        ];
    }

    public function attributes(): array
    {
        return [
            'firstname' => __('tenant::message.firstname'),
            'lastname' => __('tenant::message.lastname'),
            'name_prefix' => __('tenant::message.name_prefix'),
            'email' => __('tenant::message.email'),
            'mobile' => __('tenant::message.mobile'),
            'date_of_birth' => __('tenant::message.date_of_birth'),
            'gender' => __('tenant::message.gender'),
            'occupation' => __('tenant::message.occupation'),
            'pg_id' => __('tenant::message.pg'),
            'room_id' => __('tenant::message.room'),
            'bed_no' => __('tenant::message.bed_no'),
            'checkin_date' => __('tenant::message.checkin_date'),
            'expected_checkout_date' => __('tenant::message.expected_checkout_date'),
            'monthly_rent' => __('tenant::message.monthly_rent'),
            'security_deposit' => __('tenant::message.security_deposit'),
            'payment_method' => __('tenant::message.payment_method'),
            'id_proof_type' => __('tenant::message.id_proof_type'),
            'id_proof_number' => __('tenant::message.id_proof_number'),
            'id_proof_file' => __('tenant::message.id_proof_file'),
            'emergency_contact_name' => __('tenant::message.emergency_contact_name'),
            'emergency_relation' => __('tenant::message.emergency_relation'),
            'emergency_contact_number' => __('tenant::message.emergency_contact_number'),
            'permanent_state_id' => __('tenant::message.permanent_state'),
            'permanent_city_id' => __('tenant::message.permanent_city'),
            'permanent_address' => __('tenant::message.permanent_address'),
            'additional_notes' => __('tenant::message.additional_notes'),
        ];
    }

    public function messages(): array
    {
        return [
            'firstname.required' => __('tenant::message.enter_firstname'),
            'email.required' => __('tenant::message.enter_email'),
            'email.unique' => __('tenant::message.email_taken'),
            'mobile.required' => __('tenant::message.enter_mobile'),
            'mobile.unique' => __('tenant::message.mobile_taken'),
            'id_proof_file.max' => __('tenant::message.id_proof_file_max'),
            'id_proof_file.mimes' => __('tenant::message.id_proof_file_mimes'),
            'expected_checkout_date.after_or_equal' => __('tenant::message.checkout_after_checkin'),
        ];
    }
}
