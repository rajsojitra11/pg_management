<?php

namespace Modules\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'pg_id' => ['required', 'integer', 'exists:pg_management,id'],
            'room_id' => ['required', 'integer', 'exists:pg_rooms,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string', 'max:50', 'in:Cash,Bank Transfer,Cheque,UPI,Card,Other'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'tenant_id' => __('payment::message.tenant'),
            'pg_id' => __('payment::message.pg'),
            'room_id' => __('payment::message.room_no'),
            'payment_date' => __('payment::message.payment_date'),
            'amount' => __('payment::message.amount'),
            'payment_method' => __('payment::message.payment_method'),
            'reference_no' => __('payment::message.reference_no'),
            'remarks' => __('payment::message.remarks'),
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.required' => __('payment::message.select_tenant'),
            'pg_id.required' => __('payment::message.select_pg'),
            'room_id.required' => __('payment::message.select_room'),
            'amount.required' => __('payment::message.enter_amount'),
            'amount.min' => __('payment::message.enter_amount'),
        ];
    }
}
