<?php

namespace App\Http\Resources\Api\V1;

use App\Services\Auth\WechatLoginResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WechatLoginResult */
class WechatLoginResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'access_token' => $this->tokens->accessToken,
            'refresh_token' => $this->tokens->refreshToken,
            'expires_in' => $this->tokens->accessExpiresIn,
            'refresh_expires_in' => $this->tokens->refreshExpiresIn,
            'user' => MiniProgramUserResource::make($this->user)->resolve($request),
            'is_new_user' => $this->isNew,
        ];
    }
}
