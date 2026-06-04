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
        return [
            'user_remark' => 'required|string|min:'.config('app.min_comment_length', 3).'|max:'.config('app.max_comment_length', 1000),
        ];
    }

    public function messages(): array
    {
        return [
            'user_remark.required' => __('validation.user_remark_required'),
            'user_remark.min' => __('validation.user_remark_min', ['min' => config('app.min_comment_length', 3)]),
            'user_remark.max' => __('validation.user_remark_max', ['max' => config('app.max_comment_length', 1000)]),
        ];
    }
}
