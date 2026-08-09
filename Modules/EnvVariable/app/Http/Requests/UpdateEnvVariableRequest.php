<?php

namespace Modules\EnvVariable\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnvVariableRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('options')) {
            $decoded = json_decode($this->input('options'), true);
            if (is_array($decoded)) {
                $this->merge(['options' => $decoded]);
            }
        }
    }

    public function rules(): array
    {
        $envVariable = $this->route('env_variable');
        $id = $envVariable ? $envVariable->id : $this->route('id');

        $rules = [
            'key' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Z][A-Z0-9_]*$/',
                Rule::unique('env_variables', 'key')->ignore($id),
            ],
            'value' => 'nullable|string|max:5000',
            'type' => 'required|string|in:text,number,boolean,select,password',
            'options' => 'nullable|array',
            'options.*' => 'required|string',
            'category' => 'nullable|string|max:100',
            'validation_rules' => 'nullable|string|max:'.config('app.max_comment_length', 1000),
            'description' => 'nullable|string|max:'.config('app.max_comment_length', 1000),
            'is_encrypted' => 'boolean',
            'is_sensitive' => 'boolean',
            'is_editable' => 'boolean',
            'requires_restart' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'sync_env_file' => 'boolean',
        ];

        // Add specific validation for performance variables
        if ($this->input('key') === 'SESSION_LIFETIME') {
            $rules['value'] = 'required|integer|min:30|max:1440';
        }

        if ($this->input('key') === 'BCRYPT_ROUNDS') {
            $rules['value'] = 'required|integer|min:10|max:15';
        }

        if ($this->input('key') === 'PHP_CLI_SERVER_WORKERS') {
            $rules['value'] = 'required|integer|min:1|max:8';
        }

        if ($this->input('key') === 'CACHE_PREFIX') {
            $rules['value'] = 'required|string|max:50|alpha_dash';
        }

        if (in_array($this->input('key'), ['SESSION_DRIVER', 'CACHE_DRIVER', 'QUEUE_CONNECTION', 'LOG_LEVEL', 'APP_MAINTENANCE_DRIVER'])) {
            $rules['value'] = 'required|string|max:255';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'key.required' => __('envvariable::message.validation.key_required'),
            'key.unique' => __('envvariable::message.validation.key_unique'),
            'key.regex' => __('envvariable::message.validation.key_format'),
            'value.max' => __('envvariable::message.validation.value_max'),
            'description.max' => __('envvariable::message.validation.description_max'),
            'options.array' => __('envvariable::message.validation.options_invalid'),
            'options.*.required' => __('envvariable::message.validation.options_item_required'),
            'options.*.string' => __('envvariable::message.validation.options_item_string'),
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
