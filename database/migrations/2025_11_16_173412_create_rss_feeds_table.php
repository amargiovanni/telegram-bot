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
        Schema::create('rss_feeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegraph_bot_id')->constrained('telegraph_bots')->cascadeOnDelete();
            $table->foreignId('telegraph_chat_id')->nullable()->constrained('telegraph_chats')->nullOnDelete();
            $table->string('name');
            $table->string('url');
            $table->unsignedInteger('check_interval')->default(60); // minutes
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_entry_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('filters')->nullable(); // For advanced filtering
            $table->timestamps();

            $table->index(['telegraph_bot_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rss_feeds');
    }
};
