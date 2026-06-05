<?php

namespace Modules\PgManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeletePgManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'deleted_by' => ['nullable', 'exists:users,id'],
        ];
    }
}
