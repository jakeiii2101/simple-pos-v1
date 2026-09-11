<?php

namespace App\Support;

use App\Models\BirSetting;
use App\Models\InvoiceSequence;
use App\Models\Sale;
use Illuminate\Support\Facades\Storage;

class SystemReadinessService
{
    /** @return array<string, mixed> */
    public function inspect(): array
    {
        $sequence = InvoiceSequence::query()
            ->where('document_type', InvoiceSequence::TYPE_SALES_INVOICE)
            ->where('is_active', true)
            ->first();
        $numbers = [];
        $malformed = [];

        if ($sequence !== null) {
            foreach (Sale::query()->whereNotNull('invoice_number')->pluck('invoice_number') as $invoice) {
                if (preg_match('/^'.preg_quote($sequence->prefix, '/').'(\d+)$/', $invoice, $matches) === 1) {
                    $numbers[] = (int) $matches[1];
                } else {
                    $malformed[] = $invoice;
                }
            }
        }

        sort($numbers);
        $missing = [];
        if ($sequence !== null) {
            $present = array_flip($numbers);
            $upper = $sequence->current_number;
            for ($number = $sequence->starting_number; $number <= $upper && count($missing) < 100; $number++) {
                if (! isset($present[$number])) {
                    $missing[] = $number;
                }
            }
        }

        $backupFiles = collect(Storage::disk('local')->files('backups'))
            ->reject(fn (string $file): bool => str_ends_with($file, '.sha256'))
            ->sortByDesc(fn (string $file): int => Storage::disk('local')->lastModified($file));
        $latestBackup = $backupFiles->first();

        return [
            'bir_configured' => BirSetting::query()->where('is_active', true)->exists(),
            'sequence_configured' => $sequence !== null,
            'sequence' => $sequence,
            'invoice_count' => count($numbers),
            'missing_numbers' => $missing,
            'missing_truncated' => count($missing) === 100,
            'malformed_invoices' => array_slice($malformed, 0, 100),
            'sequence_behind' => $sequence !== null && $numbers !== [] && max($numbers) > $sequence->current_number,
            'latest_backup' => $latestBackup === null ? null : [
                'name' => basename($latestBackup),
                'size' => Storage::disk('local')->size($latestBackup),
                'created_at' => Storage::disk('local')->lastModified($latestBackup),
            ],
            'backup_count' => $backupFiles->count(),
        ];
    }
}
