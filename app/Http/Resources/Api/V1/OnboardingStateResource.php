<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OnboardingStateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $state = is_array($this->resource) ? $this->resource : [];

        return [
            'current_step' => is_int($state['current_step'] ?? null)
                ? $state['current_step']
                : 0,
            'started_at' => is_string($state['started_at'] ?? null)
                ? $state['started_at']
                : null,
            'completed_at' => is_string($state['completed_at'] ?? null)
                ? $state['completed_at']
                : null,
            'skipped' => is_bool($state['skipped'] ?? null)
                ? $state['skipped']
                : false,
            'selected_member_id' => is_string($state['selected_member_id'] ?? null)
                ? $state['selected_member_id']
                : null,
            'selected_medicine_id' => is_string($state['selected_medicine_id'] ?? null)
                ? $state['selected_medicine_id']
                : null,
        ];
    }
}
