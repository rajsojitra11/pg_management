<?php

namespace Modules\Subscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscriber_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subscriptions')->whereNull('deleted_at'),
            ],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'plan_type' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_status' => ['required', 'string', 'max:50'],
        ];
    }

    public function attributes(): array
    {
        return [
            'subscriber_name' => __('subscription::message.subscriber_name'),
            'email' => __('subscription::message.email'),
            'phone' => __('subscription::message.phone'),
            'plan_type' => __('subscription::message.plan_type'),
            'start_date' => __('subscription::message.start_date'),
            'end_date' => __('subscription::message.end_date'),
            'status' => __('subscription::message.status'),
            'amount' => __('subscription::message.amount'),
            'payment_status' => __('subscription::message.payment_status'),
        ];
    }

    public function messages(): array
    {
        return [
            'subscriber_name.required' => __('subscription::message.enter_name'),
            'subscriber_name.unique' => __('subscription::message.enter_unique_name'),
            'email.required' => __('subscription::message.enter_email'),
            'email.email' => __('subscription::message.enter_valid_email'),
            'phone.required' => __('subscription::message.enter_phone'),
            'plan_type.required' => __('subscription::message.enter_plan_type'),
            'start_date.required' => __('subscription::message.enter_start_date'),
            'end_date.required' => __('subscription::message.enter_end_date'),
            'end_date.after_or_equal' => __('subscription::message.end_date_after_start'),
            'status.required' => __('subscription::message.enter_status'),
            'amount.required' => __('subscription::message.enter_amount'),
            'payment_status.required' => __('subscription::message.enter_payment_status'),
        ];
    }
}
