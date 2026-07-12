<?php

namespace Modules\Maintenance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteMaintenanceRequest extends FormRequest
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
