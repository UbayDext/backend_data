<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SertifikationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'file_path'   => $this->file_path,
            'file_url'    => $this->file_url, // hasil accessor di model
            'student_id'  => $this->student_id,
            'studi_id'    => $this->studi_id,
            'ekskul_id'   => $this->ekskul_id,
            'classroom_id'=> $this->classroom_id,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
