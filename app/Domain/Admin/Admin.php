<?php

namespace App\Domain\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Admin extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'admins';

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'is_active'     => 'boolean',
            'password'      => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Admin $admin): void {
            if (empty($admin->uuid)) {
                $admin->uuid = (string) Str::uuid();
            }
        });
    }
}
