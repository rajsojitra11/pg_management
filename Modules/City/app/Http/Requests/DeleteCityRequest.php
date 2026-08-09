<?php

namespace Modules\City\Http\Requests;

use App\Rules\ExistsByAnyKey;
use Illuminate\Foundation\Http\FormRequest;
use Modules\City\Models\City;

class DeleteCityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => ['required', new ExistsByAnyKey(City::class, 'city::message.city_not_exist')],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'id.required' => 'City ID is required for deletion.',
        ];
    }
}
