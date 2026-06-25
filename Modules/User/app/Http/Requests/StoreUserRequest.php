<?php

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
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
            'name_prefix' => 'nullable|string|max:20',
            'firstname' => 'required|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'email' => [
                'nullable',
                'email',
                Rule::unique('users')->where(function ($query) {
                    return $query->where('deleted_at', '=', null);
                }),
            ],
            'mobile' => [
                'required',
                Rule::unique('users')->where(function ($query) {
                    return $query->where('deleted_at', '=', null);
                }),
                'numeric',
                'digits:10',
            ],
            'username' => [
                'required',
                'regex:/^\S+$/',
                Rule::unique('users')->where(function ($query) {
                    return $query->where('deleted_at', '=', null);
                }),
            ],
            'password' => 'required_with:confirm_password|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/',
            'confirm_password' => 'required|same:password',
            'parent_id' => 'required|integer|exists:users,id',
            'roles' => 'required|array',
            'state_id' => 'nullable|integer|exists:states,id',
            'city_id' => 'nullable|integer|exists:cities,id',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'status' => 'required|string|in:Active,InActive',
            'dateofbirth' => 'nullable|date',
            // IMPORTANT: NO user_remark field for CREATE operations
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.unique' => __('user::message.email_unique'),
            'mobile.required' => __('user::message.enter_mobile'),
            'mobile.numeric' => __('user::message.enter_mobile'),
            'mobile.digits' => __('user::message.enter_10_digits'),
            'username.required' => __('user::message.enter_username'),
            'username.regex' => __('user::message.username_no_spaces'),
            'password.min' => __('user::message.enter_password_min'),
            'password.regex' => __('user::message.enter_password_regex'),
            'parent_id.required' => __('user::message.select_parent_user'),
            'parent_id.integer' => __('user::message.select_parent_user'),
            'roles.required' => __('user::message.select_role'),
        ];
    }
}
