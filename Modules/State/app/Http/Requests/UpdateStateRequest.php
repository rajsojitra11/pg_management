<?php

namespace Modules\State\Http\Requests;

use App\Rules\ExistsByAnyKey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\State\Models\State;

class UpdateStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = State::findByAnyKey($this->route('state') ?? $this->input('id'))?->id;

        return [
            'id' => ['required', new ExistsByAnyKey(State::class)],
            'name' => [
                'required',
                'string',
                'max:'.config('app.max_comment_length', 1000),
                Rule::unique('states')->where(function ($query) use ($id) {
                    return $query->where([
                        ['deleted_at', null],
                        ['id', '!=', $id],
                    ]);
                }),
            ],
            'code' => 'nullable|string|max:10',
            'country_id' => 'required|exists:countries,id',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'State ID is required for update.',
            'id.exists' => 'Selected state does not exist.',
            'name.required' => __('state::message.enter_name'),
            'name.unique' => __('state::message.enter_unique_name'),
            'name.max' => __('validation.max.string', ['attribute' => 'name', 'max' => config('app.max_comment_length', 1000)]),
            'code.max' => __('validation.max.string', ['attribute' => 'code', 'max' => 10]),
            'country_id.required' => __('state::message.country_required'),
            'country_id.exists' => __('state::message.country_exists'),
        ];
    }
}
