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
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $id = Unit::findByAnyKey($this->route('unit') ?? $this->input('id'))?->id;

        $rules = [
            'id' => ['required', new ExistsByAnyKey(Unit::class)],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('units')->where(function ($query) use ($id) {
                    return $query->whereNull('deleted_at')->where('id', '!=', $id);
                }),
            ],
            'unit_value' => 'nullable|numeric|min:0',
            'child_id.*' => 'nullable|integer|min:0|:units,id',
            'segment_value.*' => 'nullable|numeric|min:0',
        ];

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('unit::message.enter_unit'),
            'name.unique' => __('unit::message.enter_unique_unit'),
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->addImplicitExtension('childUnitRequired', function ($attribute, $value, $parameters) {
            $index = explode('.', $attribute)[1] ?? 0;
            $childId = $this->input("child_id.$index");

            if (! empty($childId) && $childId != '0' && empty($value)) {
                return false;
            }

            return true;
        });

        $validator->sometimes('segment_value.*', 'childUnitRequired', function ($input) {
            return true;
        });
    }
}
