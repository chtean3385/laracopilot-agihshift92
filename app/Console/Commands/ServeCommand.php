<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ServeCommand as BaseServeCommand;
use Symfony\Component\Process\Process;

/**
 * Overrides Laravel's built-in `artisan serve` to also run the task
 * scheduler in the background.  This ensures scheduled jobs (backups,
 * WhatsApp reminders, etc.) execute in both development and production
 * without requiring a separate process manager or cron daemon.
 */
class ServeCommand extends BaseServeCommand
{
    /**
     * Keep the same name so this replaces the framework's `serve` command.
     */
    protected $name = 'serve';

    protected $description = 'Serve the application on the PHP development server (with scheduler)';

    /** @var Process|null */
    private ?Process $schedulerProcess = null;

    public function handle(): int
    {
        $this->startScheduler();

        try {
            return parent::handle();
        } finally {
            $this->stopScheduler();
        }
    }

    private function startScheduler(): void
    {
        try {
            $this->schedulerProcess = new Process(
                [PHP_BINARY, 'artisan', 'schedule:work', '--no-interaction'],
                base_path(),
                null,
                null,
                null  // no timeout
            );

            $this->schedulerProcess->start(function (string $type, string $output): void {
                // Forward scheduler output to the console
                $this->getOutput()->write($output);
            });

            $this->line('<info>Scheduler started</info> (PID: ' . $this->schedulerProcess->getPid() . ')');
        } catch (\Throwable $e) {
            $this->warn('Could not start scheduler: ' . $e->getMessage());
        }
    }

    private function stopScheduler(): void
    {
        if ($this->schedulerProcess && $this->schedulerProcess->isRunning()) {
            $this->schedulerProcess->stop(3);
        }
    }
}
