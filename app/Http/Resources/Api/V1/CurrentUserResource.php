<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrentUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'display_name' => $this->display_name,
            'avatar_url' => $this->avatar_url,
            'status' => $this->status,
            'last_active_at' => $this->last_active_at?->toISOString(),
        ];
    }
}
