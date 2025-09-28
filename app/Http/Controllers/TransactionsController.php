<?php

namespace App\Http\Controllers;

use App\Models\Transfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View as ViewFacade;

class TransactionsController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        // Filters
        $q = trim((string) $request->query('q', ''));
        $from = $request->query('from');
        $to = $request->query('to');
        $status = $request->query('status');

        $query = Transfer::query()->ownedBy($userId);
        if ($q !== '') {
            $query->search($q);
        }
        if ($from || $to) {
            $query->dateBetween($from, $to);
        }
        if ($status) {
            $query->where(function ($x) use ($status) {
                $x->where('status', 'like', "%$status%")
                  ->orWhere('payin_status', 'like', "%$status%")
                  ->orWhere('payout_status', 'like', "%$status%");
            });
        }

        $transfers = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        // All-time totals (unfiltered)
        $allTimeBase = Transfer::query()->ownedBy($userId);
        $totalSentAllTime = (int) $allTimeBase->sum('amount_xaf');
        $totalPaidAllTime = (int) Transfer::query()->ownedBy($userId)->sum('total_pay_xaf');

        return view('transactions.index', [
            'transfers' => $transfers,
            'filters' => [
                'q' => $q,
                'from' => $from,
                'to' => $to,
                'status' => $status,
            ],
            'totalSentAllTime' => $totalSentAllTime,
            'totalPaidAllTime' => $totalPaidAllTime,
        ]);
    }

    public function show(Request $request, Transfer $transfer)
    {
        $userId = Auth::id();
        abort_if($transfer->user_id !== $userId, 403);
        return view('transactions.show', [
            't' => $transfer,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $userId = Auth::id();
        $q = trim((string) $request->query('q', ''));
        $from = $request->query('from');
        $to = $request->query('to');
        $status = $request->query('status');

        $query = Transfer::query()->ownedBy($userId);
        if ($q !== '') {
            $query->search($q);
        }
        if ($from || $to) {
            $query->dateBetween($from, $to);
        }
        if ($status) {
            $query->where(function ($x) use ($status) {
                $x->where('status', 'like', "%$status%")
                  ->orWhere('payin_status', 'like', "%$status%")
                  ->orWhere('payout_status', 'like', "%$status%");
            });
        }

        $items = $query->orderByDesc('created_at')->limit(2000)->get();

        $data = [
            'items' => $items,
            'generatedAt' => now(),
            'filters' => [
                'q' => $q,
                'from' => $from,
                'to' => $to,
                'status' => $status,
            ],
        ];

        // Graceful fallback if DomPDF is not installed
        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return response()->view('transactions.pdf', $data);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('transactions.pdf', $data);
        $filename = 'transactions-' . now()->format('Ymd-Hi') . '.pdf';
        return $pdf->download($filename);
    }
}
