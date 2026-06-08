<?php

namespace Modules\Noticeboard\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteNoticeboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'deleted_by' => ['nullable', 'exists:users,id'],
        ];
    }
}
