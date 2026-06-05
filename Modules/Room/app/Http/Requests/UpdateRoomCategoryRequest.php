<?php

namespace Modules\Room\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Room\Models\RoomCategory;

class UpdateRoomCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = RoomCategory::findByAnyKey($this->route('room_category') ?? $this->input('id'))?->id;

        return [
            'pg_id' => ['required', 'exists:pg_management,id'],
            'category_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('pg_room_categories')->ignore($id)->whereNull('deleted_at'),
            ],
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
