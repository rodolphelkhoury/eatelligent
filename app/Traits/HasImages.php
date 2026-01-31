<?php

namespace App\Traits;

use App\Models\Image;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasImages
{
    /**
     * Get all images for the model.
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'owner');
    }

    /**
     * Get the primary/featured image for the model.
     */
    public function image(): MorphOne
    {
        return $this->morphOne(Image::class, 'owner');
    }

    /**
     * Get the first image URL or a default.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image?->url();
    }
}
