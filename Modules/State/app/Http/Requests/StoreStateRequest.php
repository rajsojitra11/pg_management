<?php

namespace Modules\State\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:'.config('app.max_comment_length', 1000),
                Rule::unique('states')->where(function ($query) {
                    return $query->where('deleted_at', null);
                }),
            ],
            'code' => 'nullable|string|max:10',
            'country_id' => 'required|exists:countries,id,deleted_at,NULL',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('state::message.enter_name'),
            'name.unique' => __('state::message.enter_unique_name'),
            'name.max' => __('validation.max.string', ['attribute' => 'name', 'max' => config('app.max_comment_length', 1000)]),
            'code.max' => __('validation.max.string', ['attribute' => 'code', 'max' => 10]),
            'country_id.required' => __('state::message.country_required'),
            'country_id.exists' => __('state::message.country_exists'),
        ];
    }
}
