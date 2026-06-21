<?php

namespace Modules\Email\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'placeholders' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => __('email::message.validation.subject_required'),
            'body.required' => __('email::message.validation.body_required'),
        ];
    }
}
