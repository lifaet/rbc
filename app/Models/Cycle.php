<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cycle extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'open_date', 'close_date', 'status'];

    protected function casts(): array
    {
        return [
            'open_date' => 'date',
            'close_date' => 'date',
        ];
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open'
            && now()->toDateString() >= $this->open_date->toDateString()
            && now()->toDateString() <= $this->close_date->toDateString();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'open');
    }
}
