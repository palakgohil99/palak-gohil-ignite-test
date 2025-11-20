<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BooksResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,
            'title' => $this->title,
            'gutenberg_id' => $this->gutenberg_id,
            'authors' => $this->authors->makeHidden('pivot'),
            'subjects' => $this->subjects->pluck('name'),
            'languages' => $this->languages->pluck('code'),
            'bookshelves' => $this->bookshelves->pluck('name'),
            'media_type' => $this->media_type,
            'formats' => $this->formats->pluck('url', 'mime_type')->toArray(),
            'download_count' => $this->download_count,
        ];
    }
}
