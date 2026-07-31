<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MiniProgramUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'nickname' => $this->display_name,
            'avatar_url' => $this->avatar_url,
            'tracking_enabled' => $this->tracking_enabled,
            'privacy_v1_1_seen' => $this->privacy_v1_1_seen,
            'onboarding' => OnboardingStateResource::make($this->onboarding)->resolve($request),
        ];
    }
}
