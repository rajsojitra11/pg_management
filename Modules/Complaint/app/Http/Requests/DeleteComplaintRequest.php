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
        return [];
    }

    public function messages(): array
    {
        return [];
    }
}
