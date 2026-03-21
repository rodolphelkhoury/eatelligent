<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    use HasFactory;

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
        'creator_type',
        'creator_id',
    ];

    protected $appends = ['url', 'filesize_human', 'dimensions'];

    protected $hidden = [
        'creator_type',
        'creator_id',
        'creator',
    ];

    protected static function booted(): void
    {
        static::creating(function (Image $image) {
            if (auth()->check()) {
                $image->creator()->associate(auth()->user());
            }
        });
    }

    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
            'filesize' => 'integer',
        ];
    }

    public function owner()
    {
        return $this->morphTo();
    }

    public function creator()
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): string
    {
        if ($this->disk === 'imagekit') {
            $urlEndpoint = rtrim(config('services.imagekit.url_endpoint'), '/');

            return $urlEndpoint.$this->filepath;
        }

        return Storage::disk($this->disk)->url($this->filepath);
    }

    public function path(): string
    {
        return Storage::disk($this->disk)->path($this->filepath);
    }

    public function getFilesizeHumanAttribute(): string
    {
        $bytes = $this->filesize;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public function getDimensionsAttribute(): ?string
    {
        if ($this->width && $this->height) {
            return "{$this->width}x{$this->height}";
        }

        return null;
    }
}
