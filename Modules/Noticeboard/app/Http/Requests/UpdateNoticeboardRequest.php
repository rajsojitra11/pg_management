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
        $noticeType = $this->input('notice_type', 'image');

        $rules = [
            'pg_id' => ['required', 'exists:pg_management,id,deleted_at,NULL'],
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('noticeboards')->ignore($id)->whereNull('deleted_at'),
            ],
            'notice_type' => ['required', 'in:image,text'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];

        if ($noticeType === 'image') {
            $rules['image'] = ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'];
            $rules['description'] = ['nullable', 'string'];
        } else {
            $rules['image'] = ['nullable', 'string'];
            $rules['description'] = ['required', 'string'];
        }

        return $rules;
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
            'pg_id.exists' => __('noticeboard::message.select_valid_pg'),
            'title.required' => __('noticeboard::message.enter_title'),
            'title.unique' => __('noticeboard::message.title_taken'),
            'description.required' => __('noticeboard::message.enter_description'),
        ];
    }
}
