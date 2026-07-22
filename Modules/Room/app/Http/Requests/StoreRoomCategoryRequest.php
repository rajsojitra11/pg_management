<?php

namespace Modules\Room\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pg_id' => ['required', 'exists:pg_management,id'],
            'category_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('pg_room_categories')
                    ->whereNull('deleted_at')
                    ->where('pg_id', $this->input('pg_id')),
            ],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    public function attributes(): array
    {
        return [
            'pg_id' => __('room::message.pg'),
            'category_name' => __('room::message.category_name'),
        ];
    }

    public function messages(): array
    {
        return [
            'pg_id.required' => __('room::message.select_pg'),
            'category_name.required' => __('room::message.enter_category_name'),
            'category_name.unique' => __('room::message.category_name_taken'),
        ];
    }
}
