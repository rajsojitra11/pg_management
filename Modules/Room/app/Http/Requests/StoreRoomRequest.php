<?php

namespace Modules\Room\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pg_id' => ['required', 'exists:pg_management,id'],
            'category_id' => ['required', 'exists:pg_room_categories,id'],
            'room_no' => [
                'required',
                'string',
                'max:50',
                Rule::unique('pg_rooms')->whereNull('deleted_at'),
            ],
            'bed_capacity' => ['required', 'integer', 'min:1', 'max:20'],
            'rent_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    public function attributes(): array
    {
        return [
            'pg_id' => __('room::message.pg'),
            'category_id' => __('room::message.category'),
            'room_no' => __('room::message.room_no'),
            'bed_capacity' => __('room::message.bed_capacity'),
            'rent_amount' => __('room::message.rent_amount'),
        ];
    }

    public function messages(): array
    {
        return [
            'pg_id.required' => __('room::message.select_pg'),
            'category_id.required' => __('room::message.select_category'),
            'room_no.required' => __('room::message.enter_room_no'),
            'room_no.unique' => __('room::message.room_no_taken'),
            'bed_capacity.required' => __('room::message.enter_bed_capacity'),
            'bed_capacity.min' => __('room::message.bed_capacity_min'),
            'bed_capacity.max' => __('room::message.bed_capacity_max'),
        ];
    }
}
