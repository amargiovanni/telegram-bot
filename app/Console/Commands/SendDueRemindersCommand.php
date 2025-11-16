<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\BotLog;
use App\Models\Reminder;
use Exception;
use Illuminate\Console\Command;

class SendDueRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send due reminders to Telegram chats';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $reminders = Reminder::with(['bot', 'chat'])
            ->pending()
            ->due()
            ->get();

        if ($reminders->isEmpty()) {
            $this->info('No due reminders found.');

            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($reminders as $reminder) {
            try {
                // Send reminder message
                $message = "⏰ <b>PROMEMORIA!</b>\n\n";
                $message .= "📝 {$reminder->message}\n\n";
                $message .= "🆔 ID: <code>{$reminder->id}</code>";

                $reminder->chat->html($message)->send();

                // Mark as sent
                $reminder->markAsSent();

                // Log the sent reminder
                BotLog::log(
                    'reminder_sent',
                    $reminder->telegraph_bot_id,
                    $reminder->telegraph_chat_id,
                    "Reminder sent: {$reminder->message}",
                    ['reminder_id' => $reminder->id]
                );

                $sent++;
                $this->info("Sent reminder #{$reminder->id} to chat {$reminder->chat->name}");
            } catch (Exception $e) {
                $failed++;
                $this->error("Failed to send reminder #{$reminder->id}: {$e->getMessage()}");

                // Log the error
                BotLog::log(
                    'error',
                    $reminder->telegraph_bot_id,
                    $reminder->telegraph_chat_id,
                    "Failed to send reminder: {$e->getMessage()}",
                    [
                        'reminder_id' => $reminder->id,
                        'error' => $e->getMessage(),
                    ]
                );
            }
        }

        $this->info("Reminders processed: {$sent} sent, {$failed} failed.");

        return self::SUCCESS;
    }
}
