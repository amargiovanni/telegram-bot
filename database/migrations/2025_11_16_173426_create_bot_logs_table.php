<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bot_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegraph_bot_id')->nullable()->constrained('telegraph_bots')->cascadeOnDelete();
            $table->foreignId('telegraph_chat_id')->nullable()->constrained('telegraph_chats')->nullOnDelete();
            $table->enum('type', [
                'message_received',
                'message_sent',
                'command_executed',
                'auto_response_triggered',
                'rss_check',
                'rss_posted',
                'error',
                'webhook_received',
                'chat_registered',
                'bot_added_to_group',
                'bot_removed_from_group',
            ]);
            $table->string('message')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index(['telegraph_bot_id', 'type', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_logs');
    }
};
