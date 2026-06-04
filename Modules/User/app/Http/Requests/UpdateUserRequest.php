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
            'lastname' => 'required|string|max:255',
            'email' => [
                'nullable',
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
            'confirm_password' => 'nullable|same:password',
            'designation' => 'nullable|string|max:255',
            'state_id' => 'nullable|integer|exists:states,id',
            'city_id' => 'nullable|integer|exists:cities,id',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'roles' => 'required|array',
            'status' => 'required|string|in:Active,InActive',
            'dateofbirth' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.required' => __('user::message.enter_mobile'),
            'mobile.numeric' => __('user::message.enter_mobile'),
            'mobile.digits' => __('user::message.enter_10_digits'),
            'username.required' => __('user::message.enter_username'),
            'username.regex' => __('user::message.username_no_spaces'),
            'password.min' => __('user::message.enter_password_min'),
            'password.regex' => __('user::message.enter_password_regex'),
            'designation.required' => __('user::message.select_designation'),
            'roles.required' => __('user::message.select_role'),
        ];
    }
}
