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
            'gst_number' => 'nullable|string|max:15',
            'pancard_number' => 'nullable|string|max:10',
            'tan_number' => 'nullable|string|max:14',
            'email' => 'nullable|email|max:255',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'year_display_format' => 'nullable|in:full_short,short_full,short_short,full_full,short,full',
            'country_id' => 'required|integer|exists:countries,id,deleted_at,NULL',
            'state_id' => 'required|integer|exists:states,id,deleted_at,NULL',
            'city_id' => 'required|integer|exists:cities,id,deleted_at,NULL',
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => __('setting::message.company_name_required'),
            'company_name.max' => __('setting::message.company_name_max', ['max' => config('app.max_comment_length', 1000)]),
            'tag_line.max' => __('setting::message.tag_line_max', ['max' => config('app.max_comment_length', 1000)]),
            'email.email' => __('setting::message.email_valid'),
            'email.max' => __('setting::message.email_max'),
            'mobile.max' => __('setting::message.mobile_max'),
            'address.string' => __('setting::message.address_string'),
            'year_display_format.in' => __('setting::message.year_display_format_in'),
            'country_id.required' => __('setting::message.country_id_required'),
            'country_id.exists' => __('setting::message.country_id_exists'),
            'state_id.required' => __('setting::message.state_id_required'),
            'state_id.exists' => __('setting::message.state_id_exists'),
            'city_id.required' => __('setting::message.city_id_required'),
            'city_id.exists' => __('setting::message.city_id_exists'),
        ];
    }
}
