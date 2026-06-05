<?php

namespace Modules\Room\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteRoomRequest extends FormRequest
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
