<?php

namespace Modules\City\Http\Requests;

use App\Rules\ExistsByAnyKey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\City\Models\City;

class UpdateCityRequest extends FormRequest
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
        $id = City::findByAnyKey($this->route('city') ?? $this->input('id'))?->id;

        return [
            'id' => ['required', new ExistsByAnyKey(City::class, 'city::message.city_not_exist')],
            'name' => [
                'required',
                'string',
                'max:'.config('app.max_comment_length', 1000),
                Rule::unique('cities')->where(function ($query) use ($id) {
                    return $query->where([
                        ['deleted_at', null],
                        ['state_id', '=', $this->state_id],
                        ['id', '!=', $id],
                    ]);
                }),
            ],
            'state_id' => 'required|integer|exists:states,id,deleted_at,NULL',
            'country_id' => 'required|integer|exists:countries,id,deleted_at,NULL',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'id.required' => 'City ID is required for update.',
            'name.required' => __('city::message.enter_name'),
            'name.unique' => __('city::message.enter_unique_name'),
            'name.max' => __('validation.max.string', ['attribute' => 'name', 'max' => config('app.max_comment_length', 1000)]),
            'state_id.required' => __('city::message.select_state'),
            'state_id.exists' => __('city::message.select_state'),
            'country_id.required' => __('city::message.select_country'),
            'country_id.exists' => __('city::message.select_country'),
        ];
    }
}
