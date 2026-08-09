<?php

namespace Modules\City\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCityRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:'.config('app.max_comment_length', 1000),
                Rule::unique('cities')->where(function ($query) {
                    return $query->where([
                        ['deleted_at', null],
                        ['state_id', '=', $this->state_id],
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
