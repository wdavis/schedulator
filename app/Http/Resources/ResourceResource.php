<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResourceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'active' => $this->active,
            'booking_window_lead_override' => $this->booking_window_lead_override,
            'booking_window_end_override' => $this->booking_window_end_override,
            'cancellation_window_end_override' => $this->cancellation_window_end_override,
            'meta' => $this->meta,
//            'locations' => LocationResource::collection($this->whenLoaded('locations')),
        ];
    }
}
