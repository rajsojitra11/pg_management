<?php

namespace Modules\PgManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\PgManagement\Models\PgManagement;

class UpdatePgManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = PgManagement::findByAnyKey($this->route('pgmanagement') ?? $this->input('id'))?->id;

        return [
            'pg_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('pg_management')->ignore($id)->whereNull('deleted_at'),
            ],
            'owner_id' => ['required', 'exists:users,id,deleted_at,NULL'],
            'mobile_no' => ['required', 'string', 'max:20'],
            'total_block' => ['required', 'integer', 'min:0'],
            'total_room' => ['required', 'integer', 'min:0'],
            'country_id' => ['required', 'exists:countries,id,deleted_at,NULL'],
            'state_id' => ['required', 'exists:states,id,deleted_at,NULL'],
            'city_id' => ['required', 'exists:cities,id,deleted_at,NULL'],
            'pincode' => ['required', 'string', 'max:10'],
            'address' => ['required', 'string'],
            'status' => ['required', 'string', 'in:active,inactive'],
        ];
    }

    public function attributes(): array
    {
        return [
            'pg_name' => __('pgmanagement::message.pg_name'),
            'owner_id' => __('pgmanagement::message.owner'),
            'mobile_no' => __('pgmanagement::message.mobile_no'),
            'total_block' => __('pgmanagement::message.total_block'),
            'total_room' => __('pgmanagement::message.total_room'),
            'country_id' => __('pgmanagement::message.country'),
            'state_id' => __('pgmanagement::message.state'),
            'city_id' => __('pgmanagement::message.city'),
            'pincode' => __('pgmanagement::message.pincode'),
            'address' => __('pgmanagement::message.address'),
        ];
    }

    public function messages(): array
    {
        return [
            'pg_name.required' => __('pgmanagement::message.enter_pg_name'),
            'pg_name.unique' => __('pgmanagement::message.enter_unique_pg_name'),
            'owner_id.required' => __('pgmanagement::message.enter_owner'),
            'owner_id.exists' => __('pgmanagement::message.enter_valid_owner'),
            'mobile_no.required' => __('pgmanagement::message.enter_mobile_no'),
            'total_block.required' => __('pgmanagement::message.enter_total_block'),
            'total_room.required' => __('pgmanagement::message.enter_total_room'),
            'country_id.required' => __('pgmanagement::message.enter_country'),
            'country_id.exists' => __('pgmanagement::message.enter_valid_country'),
            'state_id.required' => __('pgmanagement::message.enter_state'),
            'state_id.exists' => __('pgmanagement::message.enter_valid_state'),
            'city_id.required' => __('pgmanagement::message.enter_city'),
            'city_id.exists' => __('pgmanagement::message.enter_valid_city'),
            'pincode.required' => __('pgmanagement::message.enter_pincode'),
            'address.required' => __('pgmanagement::message.enter_address'),
            'status.required' => __('pgmanagement::message.enter_status'),
            'status.in' => __('pgmanagement::message.enter_valid_status'),
        ];
    }
}
