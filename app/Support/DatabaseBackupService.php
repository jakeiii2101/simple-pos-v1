<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DatabaseBackupService
{
    public function create(): string
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        $directory = Storage::disk('local')->path('backups');
        File::ensureDirectoryExists($directory, 0700);
        $filename = 'sniperpos-'.now()->format('Ymd-His').($driver === 'sqlite' ? '.sqlite' : '.sql');
        $path = $directory.DIRECTORY_SEPARATOR.$filename;

        if ($driver === 'sqlite') {
            $source = config("database.connections.{$connection}.database");
            if ($source === ':memory:') {
                DB::connection($connection)->statement("VACUUM INTO '".str_replace("'", "''", $path)."'");
            } elseif (! is_string($source) || ! File::isFile($source) || ! File::copy($source, $path)) {
                throw new RuntimeException('The SQLite database could not be copied.');
            }
        } elseif ($driver === 'mysql') {
            $configuration = config("database.connections.{$connection}");
            $result = Process::env(['MYSQL_PWD' => (string) ($configuration['password'] ?? '')])
                ->timeout(300)
                ->run([
                    'mysqldump',
                    '--single-transaction',
                    '--quick',
                    '--skip-lock-tables',
                    '--host='.(string) ($configuration['host'] ?? '127.0.0.1'),
                    '--port='.(string) ($configuration['port'] ?? '3306'),
                    '--user='.(string) ($configuration['username'] ?? ''),
                    '--result-file='.$path,
                    (string) $configuration['database'],
                ]);

            if ($result->failed()) {
                throw new RuntimeException('mysqldump failed: '.$result->errorOutput());
            }
        } else {
            throw new RuntimeException('Automated backups currently support MySQL and SQLite only.');
        }

        if (! File::isFile($path) || File::size($path) === 0) {
            throw new RuntimeException('The backup file was not created correctly.');
        }

        File::put($path.'.sha256', hash_file('sha256', $path).'  '.$filename.PHP_EOL);
        $this->removeExpired();
        return $filename;
    }

    public function removeExpired(int $days = 30): void
    {
        $cutoff = now()->subDays($days)->timestamp;

        foreach (Storage::disk('local')->files('backups') as $file) {
            if (Storage::disk('local')->lastModified($file) < $cutoff) {
                Storage::disk('local')->delete($file);
            }
        }
    }
}
