<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->resource->getKey(),
            'name' => $this->name,
            'member_count' => count($this->member_openids ?? []),
            'account_members' => $this->resource
                ->resourceAccountUsers()
                ->map(fn (User $user): array => (new FamilyAccountMemberResource(
                    $user,
                    $this->resource,
                ))->resolve($request))
                ->values()
                ->all(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
