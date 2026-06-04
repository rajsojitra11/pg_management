<?php

namespace Modules\EnvVariable\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;

class EnvVariableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $value = $this->value;

        // Decrypt value if encrypted and user has permission to view
        if ($this->is_encrypted && ! empty($value)) {
            try {
                $value = Crypt::decryptString($value);
            } catch (Exception $e) {
                $value = '[Encrypted - Access Denied]';
            }
        }

        return [
            'id' => $this->id,
            'key' => $this->key,
            'value' => $value,
            'description' => $this->description,
            'is_system' => $this->is_system,
            'is_encrypted' => $this->is_encrypted,
            'group' => $this->group,
            'order_display' => $this->order_display,
            'status' => $this->status,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
        ];
    }
}
