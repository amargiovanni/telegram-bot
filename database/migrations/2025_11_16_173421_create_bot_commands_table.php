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
        Schema::create('bot_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegraph_bot_id')->constrained('telegraph_bots')->cascadeOnDelete();
            $table->string('command'); // without leading slash, e.g., "start", "help"
            $table->string('description');
            $table->text('response_text');
            $table->enum('response_type', ['text', 'photo', 'document', 'video', 'audio'])->default('text');
            $table->string('media_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('show_in_menu')->default(true); // Show in Telegram bot menu
            $table->json('allowed_chat_ids')->nullable(); // Restrict to specific chats
            $table->timestamps();

            $table->unique(['telegraph_bot_id', 'command']);
            $table->index(['telegraph_bot_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_commands');
    }
};
