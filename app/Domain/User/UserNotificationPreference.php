<?php

namespace App\Domain\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserNotificationPreference extends Model
{
    use SoftDeletes;

    protected $table = 'user_notification_preferences';

    protected $fillable = [
        'user_id',
        'channel_email',
        'channel_whatsapp',
        'channel_push',
        'rate_limit_per_minute',
        'quiet_hours_start',
        'quiet_hours_end',
    ];

    protected $hidden = ['id', 'user_id'];

    protected function casts(): array
    {
        return [
            'channel_email'    => 'boolean',
            'channel_whatsapp' => 'boolean',
            'channel_push'     => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(RecipientUser::class, 'user_id');
    }
}
