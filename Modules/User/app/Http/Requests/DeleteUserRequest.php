<?php

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteUserRequest extends FormRequest
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
            'user_remark' => 'required|string|min:'.config('app.min_comment_length', 3).'|max:'.config('app.max_comment_length', 1000),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_remark.required' => __('validation.user_remark_required'),
            'user_remark.min' => __('validation.user_remark_min', ['min' => config('app.min_comment_length', 3)]),
            'user_remark.max' => __('validation.user_remark_max', ['max' => config('app.max_comment_length', 1000)]),
        ];
    }
}
