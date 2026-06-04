<?php

namespace Modules\State\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteStateRequest extends FormRequest
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
