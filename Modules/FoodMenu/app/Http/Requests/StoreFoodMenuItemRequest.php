<?php

namespace Modules\FoodMenu\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Modules\FoodMenu\Models\FoodMenu;

class StoreFoodMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'food_menu_id' => ['required', 'exists:food_menus,id,deleted_at,NULL'],
            'day' => ['nullable', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'meal_time' => ['required', 'in:breakfast,lunch,dinner'],
            'item_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    public function attributes(): array
    {
        return [
            'meal_time' => __('foodmenu::message.meal_time'),
            'item_name' => __('foodmenu::message.item_name'),
            'day' => __('foodmenu::message.day'),
        ];
    }

    public function messages(): array
    {
        return [
            'meal_time.required' => __('foodmenu::message.select_meal_time'),
            'item_name.required' => __('foodmenu::message.enter_item_name'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $menu = FoodMenu::find((int) $this->input('food_menu_id'));
            if (is_null($menu)) {
                return;
            }

            if ($menu->menu_type === 'weekly' && blank($this->input('day'))) {
                $validator->errors()->add('day', __('foodmenu::message.select_day_for_weekly'));

                return;
            }

            if ($menu->menu_type === 'special' && filled($this->input('day'))) {
                $validator->errors()->add('day', __('foodmenu::message.day_not_allowed_for_special'));
            }
        });
    }
}
