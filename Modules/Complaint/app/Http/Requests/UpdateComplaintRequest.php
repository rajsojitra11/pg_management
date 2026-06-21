<?php

namespace Modules\Complaint\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pg_id' => 'required|exists:pg_management,id',
            'room_id' => 'required|exists:pg_rooms,id',
            'service_category_id' => 'required|exists:service_categories,id',
            'service_id' => 'required|exists:services,id',
            'complaint_date' => 'required|date',
            'note' => 'required|string',
            'user_remark' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'pg_id.required' => __('complaint::message.validation.pg_required'),
            'room_id.required' => __('complaint::message.validation.room_required'),
            'service_category_id.required' => __('complaint::message.validation.category_required'),
            'service_id.required' => __('complaint::message.validation.service_required'),
            'complaint_date.required' => __('complaint::message.validation.date_required'),
            'complaint_date.date' => __('complaint::message.validation.date_invalid'),
            'note.required' => __('complaint::message.validation.note_required'),
            'user_remark.required' => __('message.common.user_remark_required_update'),
        ];
    }
}
