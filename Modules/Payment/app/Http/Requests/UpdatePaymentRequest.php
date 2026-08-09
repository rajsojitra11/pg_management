<?php

namespace Modules\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id,deleted_at,NULL'],
            'pg_id' => ['required', 'integer', 'exists:pg_management,id,deleted_at,NULL'],
            'room_id' => ['required', 'integer', 'exists:pg_rooms,id,deleted_at,NULL'],
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
            'tenant_id.exists' => __('payment::message.select_valid_tenant'),
            'pg_id.required' => __('payment::message.select_pg'),
            'pg_id.exists' => __('payment::message.select_valid_pg'),
            'room_id.required' => __('payment::message.select_room'),
            'room_id.exists' => __('payment::message.select_valid_room'),
            'amount.required' => __('payment::message.enter_amount'),
            'amount.min' => __('payment::message.enter_amount'),
            'payment_date.required' => __('payment::message.select_payment_date'),
            'payment_date.date' => __('payment::message.payment_date_invalid'),
            'payment_method.required' => __('payment::message.select_payment_method'),
            'payment_method.in' => __('payment::message.select_payment_method'),
        ];
    }
}
