<?php

namespace Modules\FoodMenu\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\FoodMenu\Models\FoodMenu;

class UpdateFoodMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = FoodMenu::findByAnyKey($this->route('food_menu') ?? $this->input('id'))?->id;
        $menuType = $this->input('menu_type', 'weekly');

        $rules = [
            'pg_id' => ['required', 'exists:pg_management,id,deleted_at,NULL'],
            'menu_type' => ['required', 'in:weekly,special'],
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('food_menus')->ignore($id)->whereNull('deleted_at'),
            ],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];

        if ($menuType === 'weekly') {
            $rules['week_start_date'] = ['required', 'date'];
            $rules['special_date'] = ['nullable', 'date'];
        } else {
            $rules['week_start_date'] = ['nullable', 'date'];
            $rules['special_date'] = ['required', 'date'];
        }

        $rules['week_items'] = ['nullable', 'array'];
        $rules['week_items.*'] = ['array'];

        $meals = ['breakfast', 'lunch', 'dinner'];
        foreach ($meals as $meal) {
            $rules["week_items.*.{$meal}"] = ['nullable'];
            $rules["week_items.*.{$meal}.*"] = [self::itemEntryRule()];
        }

        $rules['special_items'] = ['nullable', 'array'];

        foreach ($meals as $meal) {
            $rules["special_items.{$meal}"] = ['nullable'];
            $rules["special_items.{$meal}.*"] = [self::itemEntryRule()];
        }

        return $rules;
    }

    private static function itemEntryRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (is_array($value)) {
                $name = trim((string) ($value['item_name'] ?? ''));
                if ($name === '') {
                    $fail(__('validation.required', ['attribute' => 'item name']));
                } elseif (mb_strlen($name) > 255) {
                    $fail(__('validation.max.string', ['attribute' => 'item name', 'max' => 255]));
                }

                if (array_key_exists('description', $value) && $value['description'] !== null && ! is_string($value['description'])) {
                    $fail(__('validation.string', ['attribute' => 'description']));
                }

                if (array_key_exists('id', $value) && $value['id'] !== null && ! ctype_digit((string) $value['id'])) {
                    $fail(__('validation.numeric', ['attribute' => 'id']));
                }

                return;
            }

            if (! is_string($value)) {
                $fail(__('validation.string', ['attribute' => $attribute]));

                return;
            }

            if (mb_strlen($value) > 255) {
                $fail(__('validation.max.string', ['attribute' => $attribute, 'max' => 255]));
            }
        };
    }

    public function attributes(): array
    {
        return [
            'pg_id' => __('foodmenu::message.pg'),
            'title' => __('foodmenu::message.title'),
            'menu_type' => __('foodmenu::message.menu_type'),
            'week_start_date' => __('foodmenu::message.week_start_date'),
            'special_date' => __('foodmenu::message.special_date'),
        ];
    }

    public function messages(): array
    {
        return [
            'pg_id.required' => __('foodmenu::message.select_pg'),
            'pg_id.exists' => __('foodmenu::message.select_valid_pg'),
            'title.required' => __('foodmenu::message.enter_title'),
            'title.unique' => __('foodmenu::message.title_taken'),
            'menu_type.required' => __('foodmenu::message.select_menu_type'),
            'week_start_date.required' => __('foodmenu::message.enter_week_start_date'),
            'special_date.required' => __('foodmenu::message.enter_special_date'),
        ];
    }
}
