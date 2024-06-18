<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiKeyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // get environment name when loaded
        $environment = $this->whenLoaded('environment');
        $environment_name = $environment ? $environment->name : null;

        return [
            'id' => $this->id,
            'environment_name' => $environment_name,
            'asdf' => 'test',
            'key' => $this->key,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
