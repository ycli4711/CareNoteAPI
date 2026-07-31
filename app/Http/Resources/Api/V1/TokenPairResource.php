<?php

namespace App\Http\Resources\Api\V1;

use App\Services\Auth\TokenPair;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TokenPair */
class TokenPairResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->accessExpiresIn,
            'refresh_expires_in' => $this->refreshExpiresIn,
        ];
    }
}
