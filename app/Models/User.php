<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use App\Notifications\ResetPasswordNotificationCustom;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /*--------------------------------------------------------------
    | Mass-assignable attributes
    --------------------------------------------------------------*/
    protected $fillable = [
        'name',
        'email',
        'password',
        'langue_user',
        'veriff_session',
        'veriff_url',
        'status',
        'stripe_customer_id',
        'stripe_subscription_id',
        'subscription_plan',
        'subscription_started_at',
        'subscription_ends_at',
        'subscription_status',
        'cumulative_dossier_count',
        'applied_coupon_code',
        'coupon_applied_at',
        'credits',
        'credits_expires_at',
    ];

    /*--------------------------------------------------------------
    | Hidden attributes
    --------------------------------------------------------------*/
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /*--------------------------------------------------------------
    | Attribute casting
    --------------------------------------------------------------*/
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'subscription_started_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'coupon_applied_at' => 'datetime',
            'credits_expires_at' => 'datetime',
            'credits' => 'integer',
        ];
    }

    public function sendPasswordResetNotification($token)
    {
        $locale = app()->getLocale();
        $this->notify(new ResetPasswordNotificationCustom($token, $locale));
    }

    /*--------------------------------------------------------------
    | Relationships
    --------------------------------------------------------------*/
    public function demande()
    {
        return $this->hasMany(Demande::class, 'user_id');
    }

    public function subscriptionEvents()
    {
        return $this->hasMany(SubscriptionEvent::class);
    }

    public function couponUsages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    /*--------------------------------------------------------------
    | Subscription helpers
    --------------------------------------------------------------*/

    public function isSubscriptionActive(): bool
    {
        return $this->subscription_status === 'active';
    }

    public function getSubscriptionPlan(): ?array
    {
        if (!$this->subscription_plan) {
            return null;
        }
        return config("subscriptions.plans.{$this->subscription_plan}");
    }

    public function getPlanQuota(): int
    {
        $plan = $this->getSubscriptionPlan();
        return $plan ? $plan['included_dossiers'] : 0;
    }

    public function getDossiersRemaining(): int
    {
        // If user has an active coupon, check coupon limits
        if ($this->applied_coupon_code) {
            $coupon = \App\Models\Coupon::where('code', $this->applied_coupon_code)->first();
            if ($coupon) {
                $usage = \App\Models\CouponUsage::where('coupon_id', $coupon->id)
                    ->where('user_id', $this->id)
                    ->first();
                
                $dossiers_used = $usage ? $usage->dossiers_used : 0;
                return max(0, $coupon->max_dossiers - $dossiers_used);
            }
        }
        
        // Otherwise, check subscription quota
        return max(0, $this->getPlanQuota() - $this->cumulative_dossier_count);
    }

    public function getExtraDossierPrice(): float
    {
        $plan = $this->getSubscriptionPlan();
        return $plan ? $plan['extra_dossier_price'] : 0;
    }

}