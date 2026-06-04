<?php

namespace Modules\MenuMaster\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuMasterRequest extends FormRequest
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
            'menu_title' => 'required|string|max:255',
            'menu_icon' => 'nullable|string|max:100',
            'menu_route' => 'nullable|string|max:255',
            'parent_id' => 'nullable|integer|min:0|:menu_masters,id',
            'module_name' => 'nullable|string|max:100',
            'if_can' => 'nullable|string|max:100',
            'is_main_menu' => 'nullable|boolean',
            'order_display' => 'nullable|string|max:50',
            'display_order' => 'nullable|string|max:50',
            'user_remark' => 'required|string|min:'.config('app.min_comment_length', 3).'|max:'.config('app.max_comment_length', 1000),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'menu_title' => __('menumaster::message.menu_title'),
            'menu_icon' => __('menumaster::message.menu_icon'),
            'menu_route' => __('menumaster::message.menu_route_url'),
            'parent_id' => __('menumaster::message.parent_menu'),
            'module_name' => __('menumaster::message.module_name'),
            'if_can' => __('menumaster::message.permission_required'),
            'is_main_menu' => __('menumaster::message.is_main_menu'),
            'user_remark' => __('lang.labels.user_remark'),
        ];
    }

    /**
     * Get the custom validation messages.
     */
    public function messages(): array
    {
        return [
            'menu_title.required' => __('menumaster::message.enter_menu_title'),
            'parent_id.exists' => __('menumaster::message.validation.parent_id_exists'),
            'user_remark.required' => __('validation.user_remark_required'),
            'user_remark.min' => __('validation.user_remark_min', ['min' => config('app.min_comment_length', 3)]),
            'user_remark.max' => __('validation.user_remark_max', ['max' => config('app.max_comment_length', 1000)]),
        ];
    }
}
