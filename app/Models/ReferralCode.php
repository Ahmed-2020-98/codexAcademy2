<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ReferralCode extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(ReferralRedemption::class);
    }

    public static function issueFor(User $user): self
    {
        return $user->referralCode()->firstOrCreate([], [
            'code' => static::uniqueCode($user),
            'discount_percent' => (int) Setting::get('referral_discount_percent'),
        ]);
    }

    protected static function uniqueCode(User $user): string
    {
        $prefix = Str::upper(Str::substr(Str::slug(Str::ascii($user->email ?? 'cx')), 0, 3)) ?: 'CDX';

        do {
            $code = 'CDX-'.$prefix.Str::upper(Str::random(4));
        } while (static::query()->where('code', $code)->exists());

        return $code;
    }
}
