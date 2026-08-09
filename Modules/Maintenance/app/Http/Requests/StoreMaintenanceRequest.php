<?php

namespace Modules\Maintenance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'complaint_id' => 'required|exists:complaints,id,deleted_at,NULL',
            'cost' => 'required|numeric|min:0',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
            'description' => 'nullable|string',
            'maintenance_date' => 'required|date',
            'status' => 'required|string|in:pending,in_progress,completed,cancelled',
        ];
    }

    public function messages(): array
    {
        return [
            'complaint_id.required' => __('maintenance::message.validation.complaint_required'),
            'complaint_id.exists' => __('maintenance::message.validation.complaint_invalid'),
            'cost.required' => __('maintenance::message.validation.cost_required'),
            'cost.numeric' => __('maintenance::message.validation.cost_invalid'),
            'proof.mimes' => __('maintenance::message.validation.proof_mimes'),
            'proof.max' => __('maintenance::message.validation.proof_max'),
            'maintenance_date.required' => __('maintenance::message.validation.date_required'),
            'maintenance_date.date' => __('maintenance::message.validation.date_invalid'),
            'status.required' => __('maintenance::message.validation.status_required'),
        ];
    }
}
