<?php

namespace Modules\Noticeboard\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Noticeboard\Models\Noticeboard;

class UpdateNoticeboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = Noticeboard::findByAnyKey($this->route('noticeboard') ?? $this->input('id'))?->id;

        return [
            'pg_id' => ['required', 'exists:pg_management,id'],
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('noticeboards')->ignore($id)->whereNull('deleted_at'),
            ],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    public function attributes(): array
    {
        return [
            'pg_id' => __('noticeboard::message.pg'),
            'title' => __('noticeboard::message.title'),
            'image' => __('noticeboard::message.image'),
            'description' => __('noticeboard::message.description'),
        ];
    }

    public function messages(): array
    {
        return [
            'pg_id.required' => __('noticeboard::message.select_pg'),
            'title.required' => __('noticeboard::message.enter_title'),
            'title.unique' => __('noticeboard::message.title_taken'),
        ];
    }
}
