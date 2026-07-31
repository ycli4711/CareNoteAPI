<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Family;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyAccountMemberResource extends JsonResource
{
    public function __construct(
        mixed $resource,
        private readonly Family $family,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $openId = $this->resource->identities
            ->firstWhere('provider', 'wechat_mini_program')
            ?->provider_subject;

        return [
            'id' => (string) $this->resource->getKey(),
            'nickname' => $this->resource->display_name,
            'avatar_url' => $this->resource->avatar_url,
            'is_current_user' => $openId === $this->family->resourceCurrentOpenId(),
            'is_creator' => $openId === $this->family->creator_openid,
        ];
    }
}
