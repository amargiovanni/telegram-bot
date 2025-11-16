<?php

declare(strict_types=1);

namespace App;

use App\Models\AutoResponse;
use App\Models\BotCommand;
use App\Models\BotLog;
use App\Models\ShortenedUrl;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Stringable;

class TelegramWebhookHandler extends WebhookHandler
{
    public function start(): void
    {
        $this->chat->html("👋 <b>Welcome!</b>\n\nI'm a configurable Telegram bot. Use /help to see available commands.")->send();

        BotLog::log(
            'command_executed',
            $this->bot->id,
            $this->chat->id,
            'Start command executed',
            ['user' => $this->message->from()->username()]
        );
    }

    public function help(): void
    {
        $commands = BotCommand::where('telegraph_bot_id', $this->bot->id)
            ->active()
            ->inMenu()
            ->get();

        $helpText = "📚 <b>Available Commands:</b>\n\n";

        foreach ($commands as $command) {
            $helpText .= "/{$command->command} - {$command->description}\n";
        }

        $this->chat->html($helpText)->send();
    }

    public function shorten(): void
    {
        $text = $this->message->text();
        $url = trim(str_replace('/shorten', '', $text));

        // Validate URL
        $validator = Validator::make(['url' => $url], [
            'url' => 'required|url|max:2000',
        ]);

        if ($validator->fails()) {
            $this->chat->html("❌ <b>Invalid URL</b>\n\nPlease provide a valid URL.\n\nExample: <code>/shorten https://example.com</code>")->send();

            return;
        }

        // Create shortened URL
        $shortenedUrl = ShortenedUrl::create([
            'telegraph_bot_id' => $this->bot->id,
            'telegraph_chat_id' => $this->chat->id,
            'original_url' => $url,
            'short_code' => ShortenedUrl::generateUniqueCode(),
            'is_active' => true,
        ]);

        // Log the creation
        BotLog::log(
            'url_shortened',
            $this->bot->id,
            $this->chat->id,
            "URL shortened: {$shortenedUrl->short_code}",
            [
                'short_code' => $shortenedUrl->short_code,
                'original_url' => $url,
                'short_url' => $shortenedUrl->getShortUrl(),
            ]
        );

        // Send response with shortened URL
        $response = "✅ <b>URL Shortened!</b>\n\n";
        $response .= "🔗 Short URL: <code>{$shortenedUrl->getShortUrl()}</code>\n\n";
        $response .= "📊 Original: <i>{$url}</i>";

        $this->chat->html($response)->send();
    }

    public function onChatMemberUpdated(): void
    {
        $update = $this->data->get('my_chat_member');

        if (! $update) {
            return;
        }

        $newStatus = $update['new_chat_member']['status'] ?? null;
        $chatId = $update['chat']['id'] ?? null;
        $chatTitle = $update['chat']['title'] ?? $update['chat']['first_name'] ?? 'Unknown';

        if ($newStatus === 'member' || $newStatus === 'administrator') {
            // Bot was added to a group/chat
            $this->registerOrUpdateChat($chatId, $chatTitle);

            BotLog::log(
                'bot_added_to_group',
                $this->bot->id,
                null,
                "Bot added to: {$chatTitle}",
                ['chat_id' => $chatId, 'status' => $newStatus]
            );
        } elseif ($newStatus === 'left' || $newStatus === 'kicked') {
            BotLog::log(
                'bot_removed_from_group',
                $this->bot->id,
                null,
                "Bot removed from: {$chatTitle}",
                ['chat_id' => $chatId, 'status' => $newStatus]
            );
        }
    }

    protected function handleChatMessage(Stringable $text): void
    {
        // Log incoming message
        BotLog::log(
            'message_received',
            $this->bot->id,
            $this->chat->id,
            'Message received',
            ['text' => $text->toString(), 'user' => $this->message->from()->username()]
        );

        // Check for custom commands first
        if ($text->startsWith('/')) {
            $this->handleCustomCommand($text);

            return;
        }

        // Check for auto-responses
        $this->handleAutoResponses($text->toString());
    }

    protected function handleCustomCommand(Stringable $text): void
    {
        $commandText = $text->after('/')->before(' ')->toString();

        $command = BotCommand::where('telegraph_bot_id', $this->bot->id)
            ->where('command', $commandText)
            ->active()
            ->first();

        if (! $command || ! $command->isAllowedInChat($this->chat->chat_id)) {
            return;
        }

        // Send response based on type
        match ($command->response_type) {
            'photo' => $this->chat->photo($command->media_url)->message($command->response_text)->send(),
            'document' => $this->chat->document($command->media_url)->message($command->response_text)->send(),
            'video' => $this->chat->video($command->media_url)->message($command->response_text)->send(),
            'audio' => $this->chat->audio($command->media_url)->message($command->response_text)->send(),
            default => $this->chat->html($command->response_text)->send(),
        };

        BotLog::log(
            'command_executed',
            $this->bot->id,
            $this->chat->id,
            "Command executed: /{$commandText}",
            ['command_id' => $command->id]
        );
    }

    protected function handleAutoResponses(string $text): void
    {
        $responses = AutoResponse::where('telegraph_bot_id', $this->bot->id)
            ->active()
            ->byPriority()
            ->get();

        foreach ($responses as $response) {
            if (! $response->isAllowedInChat($this->chat->chat_id)) {
                continue;
            }

            if ($response->matches($text)) {
                // Send response
                match ($response->response_type) {
                    'photo' => $this->chat->photo($response->media_url)->message($response->response_text)->send(),
                    'document' => $this->chat->document($response->media_url)->message($response->response_text)->send(),
                    'video' => $this->chat->video($response->media_url)->message($response->response_text)->send(),
                    'audio' => $this->chat->audio($response->media_url)->message($response->response_text)->send(),
                    default => $this->chat->html($response->response_text)->send(),
                };

                // Delete trigger message if configured
                if ($response->delete_trigger_message) {
                    $this->chat->deleteMessage($this->messageId)->send();
                }

                BotLog::log(
                    'auto_response_triggered',
                    $this->bot->id,
                    $this->chat->id,
                    "Auto-response triggered: {$response->name}",
                    ['response_id' => $response->id]
                );

                break; // Only trigger first matching response
            }
        }
    }

    protected function handleUnknownCommand(Stringable $text): void
    {
        BotLog::log(
            'message_received',
            $this->bot->id,
            $this->chat->id,
            'Unknown command received',
            ['command' => $text->toString()]
        );
    }

    protected function registerOrUpdateChat(int $chatId, string $chatName): void
    {
        $chat = TelegraphChat::firstOrCreate(
            [
                'telegraph_bot_id' => $this->bot->id,
                'chat_id' => $chatId,
            ],
            [
                'name' => $chatName,
            ]
        );

        if (! $chat->wasRecentlyCreated) {
            $chat->update(['name' => $chatName]);
        } else {
            BotLog::log(
                'chat_registered',
                $this->bot->id,
                $chat->id,
                "New chat registered: {$chatName}",
                ['chat_id' => $chatId]
            );
        }
    }
}
