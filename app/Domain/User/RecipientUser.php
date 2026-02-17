<?php

namespace App\Domain\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class RecipientUser extends Model
{
    use SoftDeletes;

    protected $table = 'recipient_users';

    protected $attributes = [
        'is_active' => true,
        'locale'    => 'en',
    ];

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'phone_e164',
        'locale',
        'timezone',
        'is_active',
    ];

    protected $hidden = ['id'];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (RecipientUser $user): void {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });
    }

    public function preferences(): HasOne
    {
        return $this->hasOne(UserNotificationPreference::class, 'user_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class, 'user_id');
    }
}
