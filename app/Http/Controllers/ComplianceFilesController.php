<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplianceFilesController extends Controller
{
    public function backup(string $filename): BinaryFileResponse
    {
        abort_unless(preg_match('/^sniperpos-\d{8}-\d{6}\.(sql|sqlite)$/', $filename) === 1, 404);
        $path = 'backups/'.$filename;
        abort_unless(Storage::disk('local')->exists($path), 404);

        return response()->download(Storage::disk('local')->path($path), $filename, ['Content-Type' => 'application/octet-stream']);
    }

    public function audit(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $from = Carbon::parse($validated['from']);
        $to = Carbon::parse($validated['to']);
        $logs = AuditLog::query()->with('user')
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('id');

        return response()->streamDownload(function () use ($logs): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Date', 'User', 'Action', 'Subject Type', 'Subject ID', 'Description', 'IP Address', 'Metadata'], ',', '"', '');
            foreach ($logs->lazy(500) as $log) {
                fputcsv($output, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $this->csvText($log->user?->name ?? 'System'),
                    $this->csvText($log->action),
                    $this->csvText($log->auditable_type),
                    $log->auditable_id,
                    $this->csvText($log->description),
                    $this->csvText($log->ip_address),
                    $this->csvText(json_encode($log->metadata, JSON_UNESCAPED_SLASHES) ?: ''),
                ], ',', '"', '');
            }
            fclose($output);
        }, 'audit-log-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function csvText(?string $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }
}
