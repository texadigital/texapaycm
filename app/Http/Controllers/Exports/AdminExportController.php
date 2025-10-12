<?php

namespace App\Http\Controllers\Exports;

use App\Models\Transfer;
use App\Models\DailyTransactionSummary;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminExportController
{
    public function transfersCsv(Request $request): StreamedResponse
    {
        $filename = 'transfers-' . now()->format('Ymd_His') . '.csv';

        $query = Transfer::query()
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('from'), fn($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('until'), fn($q) => $q->whereDate('created_at', '<=', $request->date('until')));

        $response = new StreamedResponse(function () use ($query) {
            $out = fopen('php://output', 'w');
            $hash = hash_init('sha256');

            $headers = [
                'id','user_id','payin_status','payout_status','status','amount_xaf','receive_ngn_minor','recipient_bank_name','recipient_account_number','created_at',
            ];
            fputcsv($out, $headers);
            hash_update($hash, implode(',', $headers) . "\n");

            $query->orderBy('id')->chunk(1000, function ($rows) use ($out, $hash) {
                foreach ($rows as $row) {
                    $line = [
                        $row->id,
                        $row->user_id,
                        $row->payin_status,
                        $row->payout_status,
                        $row->status,
                        $row->amount_xaf,
                        $row->receive_ngn_minor,
                        $row->recipient_bank_name,
                        $row->recipient_account_number,
                        optional($row->created_at)->toDateTimeString(),
                    ];
                    fputcsv($out, $line);
                    hash_update($hash, implode(',', array_map(fn($v) => (string) $v, $line)) . "\n");
                }
                fflush($out);
            });

            // checksum footer
            $checksum = hash_final($hash);
            fwrite($out, "# checksum_sha256,{$checksum}\n");
            fclose($out);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    public function dailySummariesCsv(Request $request): StreamedResponse
    {
        $filename = 'daily-summaries-' . now()->format('Ymd_His') . '.csv';

        $query = DailyTransactionSummary::query()
            ->when($request->filled('user_id'), fn($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('from'), fn($q) => $q->whereDate('transaction_date', '>=', $request->date('from')))
            ->when($request->filled('until'), fn($q) => $q->whereDate('transaction_date', '<=', $request->date('until')));

        $response = new StreamedResponse(function () use ($query) {
            $out = fopen('php://output', 'w');
            $hash = hash_init('sha256');

            $headers = [
                'id','user_id','transaction_date','total_amount_xaf','transaction_count','successful_amount_xaf','successful_count','created_at',
            ];
            fputcsv($out, $headers);
            hash_update($hash, implode(',', $headers) . "\n");

            $query->orderBy('id')->chunk(1000, function ($rows) use ($out, $hash) {
                foreach ($rows as $row) {
                    $line = [
                        $row->id,
                        $row->user_id,
                        optional($row->transaction_date)->toDateString(),
                        $row->total_amount_xaf,
                        $row->transaction_count,
                        $row->successful_amount_xaf,
                        $row->successful_count,
                        optional($row->created_at)->toDateTimeString(),
                    ];
                    fputcsv($out, $line);
                    hash_update($hash, implode(',', array_map(fn($v) => (string) $v, $line)) . "\n");
                }
                fflush($out);
            });

            $checksum = hash_final($hash);
            fwrite($out, "# checksum_sha256,{$checksum}\n");
            fclose($out);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }
}
