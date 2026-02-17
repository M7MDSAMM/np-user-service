<?php

namespace App\Domain\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class UserDevice extends Model
{
    use SoftDeletes;

    protected $table = 'user_devices';

    protected $fillable = [
        'uuid',
        'user_id',
        'provider',
        'token',
        'platform',
        'is_active',
        'last_seen_at',
    ];

    protected $hidden = ['id', 'user_id'];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'is_active'    => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (UserDevice $device): void {
            if (empty($device->uuid)) {
                $device->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(RecipientUser::class, 'user_id');
    }
}
