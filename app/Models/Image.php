<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'disk',
        'name',
        'filepath',
        'mimetype',
        'width',
        'height',
        'filesize',
        'owner_type',
        'owner_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
            'filesize' => 'integer',
        ];
    }

    /**
     * Get the owning model (polymorphic relation).
     */
    public function owner()
    {
        return $this->morphTo();
    }

    /**
     * Get the full URL of the image.
     */
    public function url(): string
    {
        return 'todo';
        // return Storage::disk($this->disk)->temporaryUrl($this->filepath, now()->addMinutes(60));
    }

    /**
     * Get the full path of the image.
     */
    public function path(): string
    {
        return Storage::disk($this->disk)->path($this->filepath);
    }

    /**
     * Get human-readable file size.
     */
    public function getFilesizeHumanAttribute(): string
    {
        $bytes = $this->filesize;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Get image dimensions as string.
     */
    public function getDimensionsAttribute(): ?string
    {
        if ($this->width && $this->height) {
            return "{$this->width}x{$this->height}";
        }

        return null;
    }
}
