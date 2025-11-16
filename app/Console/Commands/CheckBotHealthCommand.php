<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\BotHealthMonitor;
use Illuminate\Console\Command;

class CheckBotHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:health-check {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the health status of the bot system';

    /**
     * Execute the console command.
     */
    public function handle(BotHealthMonitor $monitor): int
    {
        $this->info('Running health checks...');
        $this->newLine();

        $result = $monitor->check();

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));

            return $result['status'] === 'healthy' ? self::SUCCESS : self::FAILURE;
        }

        // Display overall status
        $statusColor = match ($result['status']) {
            'healthy' => 'green',
            'warning' => 'yellow',
            default => 'red',
        };

        $this->line('Overall Status: <fg='.$statusColor.'>'.strtoupper($result['status']).'</>');
        $this->line('Timestamp: '.$result['timestamp']);
        $this->newLine();

        // Display individual checks
        $this->line('Individual Checks:');
        $this->newLine();

        foreach ($result['checks'] as $checkName => $checkResult) {
            $statusIcon = match ($checkResult['status']) {
                'healthy' => '✓',
                'warning' => '⚠',
                default => '✗',
            };

            $statusColor = match ($checkResult['status']) {
                'healthy' => 'green',
                'warning' => 'yellow',
                default => 'red',
            };

            $this->line(sprintf(
                '  <fg=%s>%s</> %s: %s',
                $statusColor,
                $statusIcon,
                ucfirst(str_replace('_', ' ', $checkName)),
                $checkResult['message']
            ));
        }

        $this->newLine();

        if ($result['status'] !== 'healthy') {
            $this->warn('Some health checks failed or have warnings!');

            return self::FAILURE;
        }

        $this->info('All health checks passed!');

        return self::SUCCESS;
    }
}
