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
        Schema::create('auto_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegraph_bot_id')->constrained('telegraph_bots')->cascadeOnDelete();
            $table->string('name');
            $table->json('keywords'); // Array of keywords
            $table->enum('match_type', ['exact', 'contains', 'starts_with', 'ends_with', 'regex'])->default('contains');
            $table->boolean('case_sensitive')->default(false);
            $table->text('response_text');
            $table->enum('response_type', ['text', 'photo', 'document', 'video', 'audio'])->default('text');
            $table->string('media_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(0); // Higher = executed first
            $table->boolean('delete_trigger_message')->default(false);
            $table->json('allowed_chat_ids')->nullable(); // Restrict to specific chats
            $table->timestamps();

            $table->index(['telegraph_bot_id', 'is_active', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_responses');
    }
};
