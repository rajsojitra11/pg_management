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
            'pg_id' => 'required|exists:pg_management,id,deleted_at,NULL',
            'room_id' => 'required|exists:pg_rooms,id,deleted_at,NULL',
            'service_category_id' => 'required|exists:service_categories,id,deleted_at,NULL',
            'service_id' => 'required|exists:services,id,deleted_at,NULL',
            'complaint_date' => 'required|date',
            'note' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'pg_id.required' => __('complaint::message.validation.pg_required'),
            'pg_id.exists' => __('complaint::message.validation.pg_invalid'),
            'room_id.required' => __('complaint::message.validation.room_required'),
            'room_id.exists' => __('complaint::message.validation.room_invalid'),
            'service_category_id.required' => __('complaint::message.validation.category_required'),
            'service_category_id.exists' => __('complaint::message.validation.category_invalid'),
            'service_id.required' => __('complaint::message.validation.service_required'),
            'service_id.exists' => __('complaint::message.validation.service_invalid'),
            'complaint_date.required' => __('complaint::message.validation.date_required'),
            'complaint_date.date' => __('complaint::message.validation.date_invalid'),
            'note.required' => __('complaint::message.validation.note_required'),
        ];
    }
}
