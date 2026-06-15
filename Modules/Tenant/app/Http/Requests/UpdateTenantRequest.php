<?php

namespace Modules\Tenant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tenant\Models\Tenant;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = Tenant::findByAnyKey($this->route('tenant') ?? $this->input('id'))?->id;

        return [
            'name' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('tenants')->ignore($id)->whereNull('deleted_at'),
            ],
            'firstname' => ['nullable', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:active,inactive,Active,Inactive'],

            'pg_id' => ['nullable', 'integer', 'exists:pg_management,id'],
            'room_id' => ['nullable', 'integer', 'exists:pg_rooms,id'],
            'bed_no' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date_format:d-m-Y'],
            'gender' => ['nullable', 'string', 'in:Male,Female,Other'],
            'occupation' => ['nullable', 'string', 'max:100'],

            'checkin_date' => ['nullable', 'date_format:d-m-Y'],
            'expected_checkout_date' => ['nullable', 'date_format:d-m-Y', 'after_or_equal:checkin_date'],
            'monthly_rent' => ['nullable', 'numeric', 'min:0'],
            'security_deposit' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'id_proof_type' => ['nullable', 'string', 'max:50'],
            'id_proof_number' => ['nullable', 'string', 'max:100'],
            'id_proof_file' => $this->hasFile('id_proof_file')
                ? ['file', 'mimes:jpg,jpeg,png,pdf', 'max:2048']
                : ['nullable'],

            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_relation' => ['nullable', 'string', 'max:100'],
            'emergency_contact_number' => ['nullable', 'string', 'max:20'],
            'permanent_state_id' => ['nullable', 'integer', 'exists:states,id'],
            'permanent_city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'permanent_address' => ['nullable', 'string'],
            'additional_notes' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('tenant::message.name'),
            'email' => __('tenant::message.email'),
            'phone' => __('tenant::message.phone'),
            'address' => __('tenant::message.address'),
            'status' => __('tenant::message.status'),
            'pg_id' => __('tenant::message.pg'),
            'room_id' => __('tenant::message.room'),
            'bed_no' => __('tenant::message.bed_no'),
            'date_of_birth' => __('tenant::message.date_of_birth'),
            'gender' => __('tenant::message.gender'),
            'occupation' => __('tenant::message.occupation'),
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
            'name.required' => __('tenant::message.enter_name'),
            'name.unique' => __('tenant::message.enter_unique_name'),
            'expected_checkout_date.after_or_equal' => __('tenant::message.checkout_after_checkin'),
        ];
    }
}
