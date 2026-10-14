<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Factory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'region', 'district', 'address', 'factory_type', 'trade_license_no', 'contact_person', 'contact_phone', 'worker_count', 'contact_email', 'status'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
