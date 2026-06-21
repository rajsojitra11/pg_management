<?php

namespace Modules\Complaint\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_remark' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'user_remark.required' => __('message.common.user_remark_required_delete'),
        ];
    }
}
