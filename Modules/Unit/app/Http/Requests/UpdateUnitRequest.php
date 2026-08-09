<?php

namespace Modules\Unit\Http\Requests;

use App\Rules\ExistsByAnyKey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Unit\Models\Unit;

class UpdateUnitRequest extends FormRequest
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
        // The `{unit}` route param is an implicitly-bound Unit model, not a
        // scalar. Resolve the numeric primary key so `findByAnyKey()` is never
        // handed an Eloquent model (which would crash the underlying query).
        $routeUnit = $this->route('unit');
        $id = $routeUnit instanceof Unit
            ? $routeUnit->getKey()
            : Unit::findByAnyKey($routeUnit ?? $this->input('id'))?->id;

        return [
            'id' => ['required', new ExistsByAnyKey(Unit::class, 'unit::message.unit_not_exist')],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('units')->where(function ($query) use ($id) {
                    return $query->whereNull('deleted_at')->where('id', '!=', $id);
                }),
            ],
            'unit_value' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('unit::message.enter_unit'),
            'name.unique' => __('unit::message.enter_unique_unit'),
            'unit_value.numeric' => __('unit::message.enter_valid_unit_value'),
            'unit_value.min' => __('unit::message.unit_value_min'),
        ];
    }
}
