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
            'parent_id' => 'nullable|integer|min:0|exists:menu_masters,id,deleted_at,NULL',
            'module_name' => 'nullable|string|max:100',
            'if_can' => 'nullable|string|max:100',
            'is_main_menu' => 'nullable|boolean',
            'order_display' => 'nullable|string|max:50',
            'display_order' => 'nullable|string|max:50',
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
        ];
    }
}
