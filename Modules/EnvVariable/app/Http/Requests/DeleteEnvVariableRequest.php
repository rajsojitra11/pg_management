<?php

namespace Modules\EnvVariable\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteEnvVariableRequest extends FormRequest
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
