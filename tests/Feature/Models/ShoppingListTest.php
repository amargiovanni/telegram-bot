<?php

declare(strict_types=1);

use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('shopping list can be created', function () {
    $bot = TelegraphBot::factory()->create();
    $chat = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id]);

    $list = ShoppingList::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'name' => 'Grocery Shopping',
        'description' => 'Weekly groceries',
        'is_active' => true,
    ]);

    expect($list->name)->toBe('Grocery Shopping')
        ->and($list->description)->toBe('Weekly groceries')
        ->and($list->is_active)->toBeTrue();
});

test('shopping list has bot relationship', function () {
    $bot = TelegraphBot::factory()->create();
    $chat = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id]);

    $list = ShoppingList::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'name' => 'Test List',
    ]);

    expect($list->bot)->toBeInstanceOf(TelegraphBot::class)
        ->and($list->bot->id)->toBe($bot->id);
});

test('shopping list has chat relationship', function () {
    $bot = TelegraphBot::factory()->create();
    $chat = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id]);

    $list = ShoppingList::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'name' => 'Test List',
    ]);

    expect($list->chat)->toBeInstanceOf(TelegraphChat::class)
        ->and($list->chat->id)->toBe($chat->id);
});

test('shopping list has items relationship', function () {
    $bot = TelegraphBot::factory()->create();
    $chat = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id]);

    $list = ShoppingList::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'name' => 'Test List',
    ]);

    ShoppingListItem::create([
        'shopping_list_id' => $list->id,
        'name' => 'Milk',
        'quantity' => 2,
        'unit' => 'liters',
        'position' => 1,
    ]);

    ShoppingListItem::create([
        'shopping_list_id' => $list->id,
        'name' => 'Bread',
        'quantity' => 1,
        'position' => 2,
    ]);

    expect($list->items)->toHaveCount(2)
        ->and($list->items->first()->name)->toBe('Milk');
});

test('uncheckedItems relationship returns only unchecked items', function () {
    $bot = TelegraphBot::factory()->create();
    $chat = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id]);

    $list = ShoppingList::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'name' => 'Test List',
    ]);

    // Unchecked item
    ShoppingListItem::create([
        'shopping_list_id' => $list->id,
        'name' => 'Milk',
        'is_checked' => false,
    ]);

    // Checked item
    ShoppingListItem::create([
        'shopping_list_id' => $list->id,
        'name' => 'Bread',
        'is_checked' => true,
    ]);

    expect($list->uncheckedItems)->toHaveCount(1)
        ->and($list->uncheckedItems->first()->name)->toBe('Milk');
});

test('checkedItems relationship returns only checked items', function () {
    $bot = TelegraphBot::factory()->create();
    $chat = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id]);

    $list = ShoppingList::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'name' => 'Test List',
    ]);

    // Unchecked item
    ShoppingListItem::create([
        'shopping_list_id' => $list->id,
        'name' => 'Milk',
        'is_checked' => false,
    ]);

    // Checked item
    ShoppingListItem::create([
        'shopping_list_id' => $list->id,
        'name' => 'Bread',
        'is_checked' => true,
    ]);

    expect($list->checkedItems)->toHaveCount(1)
        ->and($list->checkedItems->first()->name)->toBe('Bread');
});

test('active scope returns only active lists', function () {
    $bot = TelegraphBot::factory()->create();
    $chat = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id]);

    // Active list
    ShoppingList::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'name' => 'Active List',
        'is_active' => true,
    ]);

    // Inactive list
    ShoppingList::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'name' => 'Inactive List',
        'is_active' => false,
    ]);

    $activeLists = ShoppingList::active()->get();

    expect($activeLists)->toHaveCount(1)
        ->and($activeLists->first()->name)->toBe('Active List');
});

test('forChat scope returns lists for specific chat', function () {
    $bot = TelegraphBot::factory()->create();
    $chat1 = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id]);
    $chat2 = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id]);

    ShoppingList::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat1->id,
        'name' => 'Chat 1 List',
    ]);

    ShoppingList::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat2->id,
        'name' => 'Chat 2 List',
    ]);

    $chat1Lists = ShoppingList::forChat($chat1->id)->get();

    expect($chat1Lists)->toHaveCount(1)
        ->and($chat1Lists->first()->name)->toBe('Chat 1 List');
});
