<?php

namespace App\Domains\Users\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfessionalUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);

        unset($data['subcategory_id']);

        return array_merge($data, [
            'subcategory' => [
                'id' => $this->subcategory ? $this->subcategory->id : null,
                'name' => $this->subcategory ? $this->subcategory->name : null,
                'category' => [
                    'id' => ($this->subcategory && $this->subcategory->category) ? $this->subcategory->category->id : null,
                    'name' => ($this->subcategory && $this->subcategory->category) ? $this->subcategory->category->name : null,
                ],
            ],
        ]);
    }
}
