<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Form extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'closes_at' => 'datetime',
            'collect_identity' => 'boolean',
            'identity_required' => 'boolean',
            'respondent_options' => 'array',
            'allow_multiple' => 'boolean',
        ];
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('position')->orderBy('id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(FormResponse::class);
    }

    public function isAcceptingResponses(): bool
    {
        if ($this->status !== 'open') {
            return false;
        }

        return ! ($this->closes_at && $this->closes_at->isPast());
    }

    public function statusLabel(): string
    {
        if ($this->status === 'open' && $this->closes_at && $this->closes_at->isPast()) {
            return 'Tamat tempoh';
        }

        return match ($this->status) {
            'open' => 'Dibuka',
            'closed' => 'Ditutup',
            default => 'Draf',
        };
    }

    /** URL imej yang selamat untuk subfolder/domain sementara. */
    public static function img(?string $src): string
    {
        if (! $src) {
            return '';
        }

        return preg_match('#^https?://#', $src) ? $src : asset(ltrim($src, '/'));
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug(Str::limit($title, 40, '')) ?: 'borang';

        do {
            $slug = $base.'-'.Str::lower(Str::random(5));
        } while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists());

        return $slug;
    }
}
