<?php

namespace Modules\Email\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmailConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pg_id' => 'required|exists:pg_management,id,deleted_at,NULL',
            'sender_email' => 'required|email',
            'sender_name' => 'nullable|string|max:255',
            'subject_prefix' => 'nullable|string|max:100',
            'status' => 'required|string|in:active,inactive',
        ];
    }

    public function messages(): array
    {
        return [
            'pg_id.required' => __('email::message.validation.pg_required'),
            'pg_id.exists' => __('email::message.validation.pg_invalid'),
            'sender_email.required' => __('email::message.validation.email_required'),
            'sender_email.email' => __('email::message.validation.email_invalid'),
            'status.required' => __('email::message.validation.status_required'),
            'status.in' => __('email::message.validation.status_invalid'),
        ];
    }
}
