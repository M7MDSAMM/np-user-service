<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Device token uniqueness: unique(token) rather than unique(provider, token).
 *
 * FCM tokens are globally unique by nature — a single FCM token can never
 * belong to two different devices or providers simultaneously.  Using a
 * simple unique(token) is sufficient and simpler to maintain.  If a second
 * provider is added in the future, composite uniqueness can be revisited.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('recipient_users')->cascadeOnDelete();
            $table->char('uuid', 36)->unique();
            $table->enum('provider', ['fcm'])->default('fcm');
            $table->string('token', 255)->unique();
            $table->enum('platform', ['android', 'ios', 'web'])->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
