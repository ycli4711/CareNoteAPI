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
            'nickname' => $this->display_name,
            'avatar_url' => $this->avatar_url,
            'gender' => in_array($this->gender, ['male', 'female'], true)
                ? $this->gender
                : 'unset',
            'tracking_enabled' => (bool) $this->tracking_enabled,
            'privacy_v1_1_seen' => (bool) $this->privacy_v1_1_seen,
            'onboarding' => OnboardingStateResource::make($this->onboarding)->resolve($request),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
