<?php

declare(strict_types=1);

use App\Models\Reminder;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('reminder can be created', function () {
    $bot = TelegraphBot::factory()->create();
    $chat = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id]);

    $reminder = Reminder::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'message' => 'Test reminder message',
        'remind_at' => now()->addMinutes(10),
    ]);

    expect($reminder->message)->toBe('Test reminder message')
        ->and($reminder->is_sent)->toBeFalse()
        ->and($reminder->sent_at)->toBeNull();
});

test('reminder has bot relationship', function () {
    $bot = TelegraphBot::factory()->create();
    $chat = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id]);

    $reminder = Reminder::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'message' => 'Test message',
        'remind_at' => now()->addHour(),
    ]);

    expect($reminder->bot)->toBeInstanceOf(TelegraphBot::class)
        ->and($reminder->bot->id)->toBe($bot->id);
});

test('reminder has chat relationship', function () {
    $bot = TelegraphBot::factory()->create();
    $chat = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id]);

    $reminder = Reminder::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'message' => 'Test message',
        'remind_at' => now()->addHour(),
    ]);

    expect($reminder->chat)->toBeInstanceOf(TelegraphChat::class)
        ->and($reminder->chat->id)->toBe($chat->id);
});

test('pending scope returns only unsent reminders', function () {
    $bot = TelegraphBot::factory()->create();
    $chat = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id]);

    // Create pending reminder
    Reminder::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'message' => 'Pending',
        'remind_at' => now()->addHour(),
        'is_sent' => false,
    ]);

    // Create sent reminder
    Reminder::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'message' => 'Sent',
        'remind_at' => now()->subHour(),
        'is_sent' => true,
        'sent_at' => now(),
    ]);

    $pendingReminders = Reminder::pending()->get();

    expect($pendingReminders)->toHaveCount(1)
        ->and($pendingReminders->first()->message)->toBe('Pending');
});

test('due scope returns only reminders due for sending', function () {
    $bot = TelegraphBot::factory()->create();
    $chat = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id]);

    // Create due reminder
    Reminder::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'message' => 'Due',
        'remind_at' => now()->subMinutes(5),
    ]);

    // Create future reminder
    Reminder::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'message' => 'Future',
        'remind_at' => now()->addHour(),
    ]);

    $dueReminders = Reminder::due()->get();

    expect($dueReminders)->toHaveCount(1)
        ->and($dueReminders->first()->message)->toBe('Due');
});

test('markAsSent updates reminder correctly', function () {
    $bot = TelegraphBot::factory()->create();
    $chat = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id]);

    $reminder = Reminder::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'message' => 'Test',
        'remind_at' => now()->addHour(),
    ]);

    expect($reminder->is_sent)->toBeFalse()
        ->and($reminder->sent_at)->toBeNull();

    $reminder->markAsSent();

    $reminder->refresh();

    expect($reminder->is_sent)->toBeTrue()
        ->and($reminder->sent_at)->not->toBeNull();
});

test('reminder casts dates correctly', function () {
    $bot = TelegraphBot::factory()->create();
    $chat = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id]);

    $remindAt = now()->addDay();

    $reminder = Reminder::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'message' => 'Test',
        'remind_at' => $remindAt,
    ]);

    expect($reminder->remind_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($reminder->is_sent)->toBeBool();
});
