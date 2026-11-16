<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormField extends Model
{
    protected $guarded = [];

    public const TYPES = [
        'heading' => 'Tajuk / Teks pengenalan',
        'short_text' => 'Jawapan pendek',
        'long_text' => 'Jawapan panjang (komen)',
        'single_choice' => 'Pilihan tunggal',
        'multi_choice' => 'Pilihan berbilang',
        'dropdown' => 'Senarai juntai bawah',
        'rating' => 'Penilaian bintang',
        'image_choice' => 'Pilih imej / reka bentuk',
        'part_feedback' => 'Maklum balas setiap bahagian',
    ];

    /** Maklum balas bagi setiap bahagian. */
    public const PART_STATUSES = [
        'suka' => 'Suka',
        'boleh' => 'Boleh diterima',
        'ubah' => 'Perlu penambahbaikan',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'options' => 'array',
            'settings' => 'array',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ResponseAnswer::class);
    }

    public function hasOptions(): bool
    {
        return in_array($this->type, ['single_choice', 'multi_choice', 'dropdown', 'image_choice', 'part_feedback'], true);
    }

    public function isAnswerable(): bool
    {
        return $this->type !== 'heading';
    }

    /** @return array<string, array> option key => option */
    public function optionMap(): array
    {
        $map = [];
        foreach ($this->options ?? [] as $opt) {
            if (isset($opt['value'])) {
                $map[$opt['value']] = $opt;
            }
        }

        return $map;
    }

    public function optionLabel(string $key): string
    {
        return $this->optionMap()[$key]['label'] ?? $key;
    }

    public function setting(string $key, $default = null)
    {
        return ($this->settings ?? [])[$key] ?? $default;
    }

    public function maxRating(): int
    {
        return max(3, min(10, (int) $this->setting('max', 5)));
    }
}
