<?php

namespace Modules\Subscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Subscription\Models\Subscription;

class UpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = Subscription::findByAnyKey($this->route('subscription') ?? $this->input('id'))?->id;

        $rules = [
            'subscriber_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subscriptions')->ignore($id)->whereNull('deleted_at'),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'plan_type' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'string', 'max:50'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['nullable', 'string', 'max:50'],
        ];

        return $rules;
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
            'email.email' => __('subscription::message.enter_valid_email'),
            'end_date.after_or_equal' => __('subscription::message.end_date_after_start'),
        ];
    }
}
