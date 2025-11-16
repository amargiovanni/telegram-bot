<?php

declare(strict_types=1);

use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('shopping list item can be created', function () {
    $bot = TelegraphBot::factory()->create();
    $chat = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id]);

    $list = ShoppingList::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'name' => 'Grocery List',
    ]);

    $item = ShoppingListItem::create([
        'shopping_list_id' => $list->id,
        'name' => 'Milk',
        'quantity' => 2,
        'unit' => 'liters',
        'is_checked' => false,
        'position' => 1,
    ]);

    expect($item->name)->toBe('Milk')
        ->and($item->quantity)->toBe(2)
        ->and($item->unit)->toBe('liters')
        ->and($item->is_checked)->toBeFalse()
        ->and($item->position)->toBe(1);
});

test('shopping list item has shopping list relationship', function () {
    $bot = TelegraphBot::factory()->create();
    $chat = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id]);

    $list = ShoppingList::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'name' => 'Test List',
    ]);

    $item = ShoppingListItem::create([
        'shopping_list_id' => $list->id,
        'name' => 'Bread',
    ]);

    expect($item->shoppingList)->toBeInstanceOf(ShoppingList::class)
        ->and($item->shoppingList->id)->toBe($list->id);
});

test('toggleCheck method toggles is_checked status', function () {
    $bot = TelegraphBot::factory()->create();
    $chat = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id]);

    $list = ShoppingList::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'name' => 'Test List',
    ]);

    $item = ShoppingListItem::create([
        'shopping_list_id' => $list->id,
        'name' => 'Eggs',
        'is_checked' => false,
    ]);

    expect($item->is_checked)->toBeFalse();

    $item->toggleCheck();
    $item->refresh();

    expect($item->is_checked)->toBeTrue();

    $item->toggleCheck();
    $item->refresh();

    expect($item->is_checked)->toBeFalse();
});

test('unchecked scope returns only unchecked items', function () {
    $bot = TelegraphBot::factory()->create();
    $chat = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id]);

    $list = ShoppingList::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'name' => 'Test List',
    ]);

    ShoppingListItem::create([
        'shopping_list_id' => $list->id,
        'name' => 'Unchecked Item',
        'is_checked' => false,
    ]);

    ShoppingListItem::create([
        'shopping_list_id' => $list->id,
        'name' => 'Checked Item',
        'is_checked' => true,
    ]);

    $uncheckedItems = ShoppingListItem::unchecked()->get();

    expect($uncheckedItems)->toHaveCount(1)
        ->and($uncheckedItems->first()->name)->toBe('Unchecked Item');
});

test('checked scope returns only checked items', function () {
    $bot = TelegraphBot::factory()->create();
    $chat = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id]);

    $list = ShoppingList::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'name' => 'Test List',
    ]);

    ShoppingListItem::create([
        'shopping_list_id' => $list->id,
        'name' => 'Unchecked Item',
        'is_checked' => false,
    ]);

    ShoppingListItem::create([
        'shopping_list_id' => $list->id,
        'name' => 'Checked Item',
        'is_checked' => true,
    ]);

    $checkedItems = ShoppingListItem::checked()->get();

    expect($checkedItems)->toHaveCount(1)
        ->and($checkedItems->first()->name)->toBe('Checked Item');
});

test('shopping list item casts values correctly', function () {
    $bot = TelegraphBot::factory()->create();
    $chat = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id]);

    $list = ShoppingList::create([
        'telegraph_bot_id' => $bot->id,
        'telegraph_chat_id' => $chat->id,
        'name' => 'Test List',
    ]);

    $item = ShoppingListItem::create([
        'shopping_list_id' => $list->id,
        'name' => 'Test Item',
        'quantity' => 3,
        'position' => 5,
        'is_checked' => true,
    ]);

    expect($item->quantity)->toBeInt()
        ->and($item->position)->toBeInt()
        ->and($item->is_checked)->toBeBool();
});
