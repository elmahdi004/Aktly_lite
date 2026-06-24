<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'description',
        'expires_at',
        'max_users',
        'max_dossiers',
        'stripe_coupon_id',
        'active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'active' => 'boolean',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function usersRemaining(): ?int
    {
        if (!$this->max_users) {
            return null;
        }
        return max(0, $this->max_users - $this->usages()->distinct('user_id')->count());
    }

    public function dossiersRemaining(): ?int
    {
        if (!$this->max_dossiers) {
            return null;
        }
        return max(0, $this->max_dossiers - $this->usages()->sum('dossiers_used'));
    }
}
