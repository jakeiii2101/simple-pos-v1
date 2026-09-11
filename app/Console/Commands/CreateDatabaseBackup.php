<?php

namespace App\Console\Commands;

use App\Support\Audit;
use App\Support\DatabaseBackupService;
use Illuminate\Console\Command;
use Throwable;

class CreateDatabaseBackup extends Command
{
    protected $signature = 'database:backup';

    protected $description = 'Create a private SniperPOS database backup with a SHA-256 checksum';

    public function handle(DatabaseBackupService $service): int
    {
        try {
            $filename = $service->create();
            Audit::record('database.backup.created', null, 'Scheduled private database backup created.', ['filename' => $filename]);
            $this->info('Database backup created: '.$filename);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
