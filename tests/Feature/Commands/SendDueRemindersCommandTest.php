<?php

declare(strict_types=1);

use App\Models\BotLog;
use App\Models\Reminder;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Mock HTTP requests to Telegram API
    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
    ]);
});

test('command sends due reminders', function () {
    $bot = TelegraphBot::factory()->create([
        'token' => 'test_bot_token_123456789:ABCdefGHIjklMNOpqrsTUVwxyz',
    ]);
    $chat = TelegraphChat::factory()->create([
        'telegraph_bot_id' => $bot->id,
        'chat_id' => '12345678',
    ]);

    // Create due reminder (past time)
    $dueReminder = Reminder::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'message' => 'Due reminder',
        'remind_at' => now()->subMinutes(5),
        'is_sent' => false,
    ]);

    // Execute command
    Artisan::call('reminders:send');

    // Check reminder was marked as sent
    $dueReminder->refresh();
    expect($dueReminder->is_sent)->toBeTrue()
        ->and($dueReminder->sent_at)->not->toBeNull();

    // Check log was created
    $log = BotLog::where('type', 'reminder_sent')
        ->where('telegraph_bot_id', $bot->id)
        ->first();

    expect($log)->not->toBeNull();
});

test('command does not send future reminders', function () {
    $bot = TelegraphBot::factory()->create();
    $chat = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id]);

    // Create future reminder
    $futureReminder = Reminder::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'message' => 'Future reminder',
        'remind_at' => now()->addHours(2),
        'is_sent' => false,
    ]);

    // Execute command
    Artisan::call('reminders:send');

    // Check reminder was NOT sent
    $futureReminder->refresh();
    expect($futureReminder->is_sent)->toBeFalse()
        ->and($futureReminder->sent_at)->toBeNull();
});

test('command does not send already sent reminders', function () {
    $bot = TelegraphBot::factory()->create();
    $chat = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id]);

    // Create already sent reminder
    $sentReminder = Reminder::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'message' => 'Sent reminder',
        'remind_at' => now()->subHours(1),
        'is_sent' => true,
        'sent_at' => now()->subHours(1),
    ]);

    $originalSentAt = $sentReminder->sent_at;

    // Execute command
    Artisan::call('reminders:send');

    // Check sent_at hasn't changed
    $sentReminder->refresh();
    expect($sentReminder->sent_at->equalTo($originalSentAt))->toBeTrue();
});

test('command handles multiple reminders correctly', function () {
    $bot = TelegraphBot::factory()->create([
        'token' => 'test_bot_token_123456789:ABCdefGHIjklMNOpqrsTUVwxyz',
    ]);
    $chat = TelegraphChat::factory()->create([
        'telegraph_bot_id' => $bot->id,
        'chat_id' => '12345678',
    ]);

    // Create multiple due reminders
    $reminder1 = Reminder::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'message' => 'Reminder 1',
        'remind_at' => now()->subMinutes(10),
    ]);

    $reminder2 = Reminder::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'message' => 'Reminder 2',
        'remind_at' => now()->subMinutes(5),
    ]);

    // Create future reminder
    $futureReminder = Reminder::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'message' => 'Future',
        'remind_at' => now()->addHour(),
    ]);

    // Execute command
    Artisan::call('reminders:send');

    // Check due reminders were sent
    $reminder1->refresh();
    $reminder2->refresh();
    $futureReminder->refresh();

    expect($reminder1->is_sent)->toBeTrue()
        ->and($reminder2->is_sent)->toBeTrue()
        ->and($futureReminder->is_sent)->toBeFalse();
});

test('command outputs success message when no reminders', function () {
    // Execute command with no reminders in database
    Artisan::call('reminders:send');

    $output = Artisan::output();

    expect($output)->toContain('No due reminders found');
});

test('command logs errors when sending fails', function () {
    // Override the HTTP fake to throw an exception
    Http::fake(function () {
        throw new Exception('Simulated network error');
    });

    $bot = TelegraphBot::factory()->create([
        'token' => 'test_bot_token_123456789:ABCdefGHIjklMNOpqrsTUVwxyz',
    ]);
    $chat = TelegraphChat::factory()->create([
        'telegraph_bot_id' => $bot->id,
        'chat_id' => '12345678',
    ]);

    // Create due reminder
    $reminder = Reminder::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'message' => 'Test',
        'remind_at' => now()->subMinutes(5),
    ]);

    // Execute command (will fail to send but should continue)
    Artisan::call('reminders:send');

    // Even if sending fails, the reminder should still exist as unsent
    $reminder->refresh();
    expect($reminder->is_sent)->toBeFalse();

    // Check error log was created
    $log = BotLog::where('type', 'error')
        ->where('telegraph_bot_id', $bot->id)
        ->first();

    expect($log)->not->toBeNull();
});
