<?php

namespace Modules\FoodMenu\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteFoodMenuItemRequest extends FormRequest
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
