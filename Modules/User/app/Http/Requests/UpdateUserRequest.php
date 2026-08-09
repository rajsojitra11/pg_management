<?php

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\User\Models\User;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = User::findByAnyKey($this->route('user'))?->id;

        return [
            'name_prefix' => 'nullable|string|max:20',
            'firstname' => 'required|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->where(function ($query) {
                    return $query->where('deleted_at', '=', null);
                })->ignore($userId),
            ],
            'mobile' => [
                'required',
                Rule::unique('users')->where(function ($query) {
                    return $query->where('deleted_at', '=', null);
                })->ignore($userId),
                'numeric',
                'digits:10',
            ],
            'username' => [
                'required',
                'regex:/^\S+$/',
                Rule::unique('users')->where(function ($query) {
                    return $query->where('deleted_at', '=', null);
                })->ignore($userId),
            ],
            'password' => 'nullable|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/',
            'confirm_password' => 'nullable|required_with:password|same:password',
            'parent_id' => 'nullable|integer|exists:users,id,deleted_at,NULL',
            'state_id' => 'nullable|integer|exists:states,id,deleted_at,NULL',
            'city_id' => 'nullable|integer|exists:cities,id,deleted_at,NULL',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'roles' => 'required|array',
            'status' => 'required|string|in:Active,InActive,Inactive',
            'dateofbirth' => 'nullable|date',
            'address' => 'nullable|string|max:1000',
            'current_pg' => ['nullable', 'integer', 'exists:pg_management,id,deleted_at,NULL'],
        ];
    }

    public function messages(): array
    {
        return [
            'firstname.required' => __('user::message.enter_first_name'),
            'email.required' => __('user::message.enter_valid_email'),
            'email.email' => __('user::message.enter_valid_email'),
            'email.unique' => __('user::message.email_unique'),
            'mobile.required' => __('user::message.enter_mobile'),
            'mobile.numeric' => __('user::message.enter_mobile'),
            'mobile.digits' => __('user::message.enter_10_digits'),
            'username.required' => __('user::message.enter_username'),
            'username.regex' => __('user::message.username_no_spaces'),
            'password.min' => __('user::message.enter_password_min'),
            'password.regex' => __('user::message.enter_password_regex'),
            'confirm_password.same' => __('user::message.password_mismatch'),
            'confirm_password.required_with' => __('user::message.enter_confirm_password'),
            'parent_id.integer' => __('user::message.select_parent_user'),
            'parent_id.exists' => __('user::message.select_parent_user'),
            'roles.required' => __('user::message.select_role'),
            'status.in' => __('user::message.status_must_be_active_inactive'),
            'state_id.exists' => __('user::message.select_valid_state'),
            'city_id.exists' => __('user::message.select_valid_city'),
        ];
    }
}
