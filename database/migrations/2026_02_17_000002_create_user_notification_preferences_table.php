<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('recipient_users')->cascadeOnDelete();
            $table->boolean('channel_email')->default(true);
            $table->boolean('channel_whatsapp')->default(false);
            $table->boolean('channel_push')->default(false);
            $table->unsignedSmallInteger('rate_limit_per_minute')->default(5);
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
    }
};
