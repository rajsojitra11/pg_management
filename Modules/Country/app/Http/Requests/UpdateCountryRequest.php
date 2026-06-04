<?php

namespace Modules\Country\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Country\Models\Country;

class UpdateCountryRequest extends FormRequest
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
        $id = Country::findByAnyKey($this->route('country'))?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:'.config('app.max_comment_length', 1000),
                Rule::unique('countries')->where(function ($query) use ($id) {
                    return $query->where('deleted_at', null)
                        ->where('id', '!=', $id);
                }),
            ],
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('countries')->where(function ($query) use ($id) {
                    return $query->where('deleted_at', null)
                        ->where('id', '!=', $id);
                }),
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('country::message.enter_name'),
            'name.unique' => __('country::message.enter_unique_name'),
            'name.max' => __('validation.max.string', ['attribute' => 'name', 'max' => config('app.max_comment_length', 1000)]),
            'code.unique' => __('country::message.enter_unique_code'),
            'code.max' => __('validation.max.string', ['attribute' => 'code', 'max' => 10]),
        ];
    }
}
