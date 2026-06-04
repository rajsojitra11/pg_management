<?php

namespace Modules\Setting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => 'required|string|max:'.config('app.max_comment_length', 1000),
            'tag_line' => 'nullable|string|max:'.config('app.max_comment_length', 1000),
            'favicon' => 'nullable|image|max:2048',
            'logo' => 'nullable|image|max:2048',
            'logo_dark' => 'nullable|image|max:2048',
            'gst_number' => 'nullable|string|max:50',
            'pancard_number' => 'nullable|string|max:50',
            'tan_number' => 'nullable|string|max:50',
            'country_id' => 'required|integer|exists:countries,id',
            'state_id' => 'required|integer|exists:states,id',
            'city_id' => 'required|integer|exists:cities,id',
            'user_remark' => 'required|string|min:'.config('app.min_comment_length', 3).'|max:'.config('app.max_comment_length', 1000),
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => __('setting::message.company_name_required'),
            'company_name.max' => __('setting::message.company_name_max', ['max' => config('app.max_comment_length', 1000)]),
            'tag_line.max' => __('setting::message.tag_line_max', ['max' => config('app.max_comment_length', 1000)]),
            'country_id.required' => 'Country is required',
            'country_id.exists' => __('setting::message.country_id_exists'),
            'state_id.required' => 'State is required',
            'state_id.exists' => __('setting::message.state_id_exists'),
            'city_id.required' => 'City is required',
            'city_id.exists' => __('setting::message.city_id_exists'),
            'user_remark.required' => __('validation.user_remark_required'),
            'user_remark.min' => __('validation.user_remark_min', ['min' => config('app.min_comment_length', 3)]),
            'user_remark.max' => __('validation.user_remark_max', ['max' => config('app.max_comment_length', 1000)]),
        ];
    }
}
