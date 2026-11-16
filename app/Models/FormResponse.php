<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormResponse extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_test' => 'boolean'];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ResponseAnswer::class);
    }

    public function displayName(): string
    {
        return ($this->respondent_name ?: 'Tanpa nama').($this->is_test ? ' (ujian)' : '');
    }
}
