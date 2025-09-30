<?php

namespace App\Http\Controllers;

use App\Services\SafeHaven;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BankController extends Controller
{
    /**
     * GET /api/banks - cached list from Safe Haven
     */
    public function list(SafeHaven $safeHaven)
    {
        // Optional refresh to bypass cache: /api/banks?refresh=1
        $forceRefresh = (bool) request()->boolean('refresh', false);

        if ($forceRefresh) {
            Cache::forget('banks:safehaven:list');
        }

        // Try cache first
        $banks = Cache::get('banks:safehaven:list');

        // If cache miss or refresh requested, fetch live from Safe Haven
        if ($forceRefresh || !is_array($banks) || empty($banks)) {
            $resp = $safeHaven->listBanks();
            $data = $resp['data'] ?? $resp['banks'] ?? $resp;

            $valid = is_array($data) && (!isset($resp['status']) || $resp['status'] !== 'failed');
            if ($valid && count($data) > 0) {
                $normalized = array_map(function ($b) {
                    return [
                        'bankCode' => $b['bankCode'] ?? ($b['routingKey'] ?? null),
                        'name' => $b['name'] ?? '',
                        'aliases' => $b['alias'] ?? [],
                        'categoryId' => $b['categoryId'] ?? null,
                    ];
                }, $data);
                // Cache only successful, non-empty lists
                Cache::put('banks:safehaven:list', $normalized, 60 * 60 * 24);
                $banks = $normalized;
            } else {
                // Graceful fallback (includes Safe Haven sandbox bank for testing) - DO NOT cache
                $banks = [
                    ['bankCode' => '999240', 'name' => 'SAFE HAVEN SANDBOX BANK', 'aliases' => ['SANDBOX'], 'categoryId' => null],
                    ['bankCode' => '000014', 'name' => 'Access Bank', 'aliases' => [], 'categoryId' => null],
                    ['bankCode' => '000004', 'name' => 'GTBank', 'aliases' => [], 'categoryId' => null],
                    ['bankCode' => '000013', 'name' => 'Fidelity Bank', 'aliases' => [], 'categoryId' => null],
                    ['bankCode' => '000016', 'name' => 'First Bank of Nigeria', 'aliases' => [], 'categoryId' => null],
                    ['bankCode' => '000001', 'name' => 'Sterling Bank', 'aliases' => [], 'categoryId' => null],
                ];
            }
        }

        // Optional query filter
        $q = trim((string) request('q', ''));
        if ($q !== '') {
            $toLower = function ($s) {
                return function_exists('mb_strtolower') ? mb_strtolower((string) $s) : strtolower((string) $s);
            };
            $qLower = $toLower($q);
            $banks = array_values(array_filter($banks, function ($b) use ($qLower, $toLower) {
                $name = $toLower($b['name'] ?? '');
                $aliases = array_map($toLower, $b['aliases'] ?? []);
                return Str::contains($name, $qLower) || collect($aliases)->contains(function ($a) use ($qLower) { return Str::contains($a, $qLower); });
            }));
        }

        return response()->json(['banks' => $banks]);
    }

    /**
     * GET /api/banks/favorites - recent banks based on session (simple v1)
     */
    public function favorites(Request $request)
    {
        $recent = (array) $request->session()->get('recent_banks', []);
        return response()->json(['banks' => array_values($recent)]);
    }

    /**
     * POST /api/banks/suggest - try resolving via shortlist of banks
     * Body: { accountNumber: string }
     */
    public function suggest(Request $request, SafeHaven $safeHaven)
    {
        $account = preg_replace('/\D+/', '', (string) $request->input('accountNumber', ''));
        if (strlen($account) < 10) {
            return response()->json(['resolved' => false, 'suggestions' => []]);
        }

        // Load banks from cache
        $banks = Cache::remember('banks:safehaven:list', 60 * 60 * 24, function () use ($safeHaven) {
            $resp = $safeHaven->listBanks();
            $data = $resp['data'] ?? $resp['banks'] ?? $resp;
            if (!is_array($data)) { $data = []; }
            return array_map(function ($b) {
                return [
                    'bankCode' => $b['bankCode'] ?? ($b['routingKey'] ?? null),
                    'name' => $b['name'] ?? '',
                    'aliases' => $b['alias'] ?? [],
                    'categoryId' => $b['categoryId'] ?? null,
                ];
            }, $data);
        });

        // Build shortlist: session recent + top banks by common usage in NG
        $recent = (array) $request->session()->get('recent_banks', []);
        $top = [
            '000014','000015','000016','000013','000001','000004','000023','000012','000017','000003','000002','000005','000020','000027','000029','000025','000031'
        ];
        $shortlist = [];
        foreach ($recent as $rb) { $shortlist[$rb['bankCode']] = $rb; }
        foreach ($banks as $b) {
            if (in_array($b['bankCode'], $top, true)) { $shortlist[$b['bankCode']] = $b; }
        }
        // Cap shortlist to 8-10
        $shortlist = array_values(array_slice($shortlist, 0, 10));

        // Try name-enquiry sequentially; stop at first success
        foreach ($shortlist as $b) {
            try {
                $res = $safeHaven->nameEnquiry($b['bankCode'], $account);
                $ok = ($res['success'] ?? false) || (($res['statusCode'] ?? '') === 200);
                $accName = $res['account_name'] ?? ($res['data']['accountName'] ?? null);
                if ($ok && $accName) {
                    // Save recent selection
                    $this->rememberRecent($request, $b);
                    return response()->json([
                        'resolved' => true,
                        'bank' => $b,
                        'accountName' => $accName,
                        'raw' => $res,
                    ]);
                }
            } catch (\Throwable $e) {
                // continue to next bank
            }
        }

        // If not resolved, return suggestions = shortlist or top 20
        $suggestions = $shortlist;
        if (count($suggestions) < 6) {
            // Fill with top banks from full list
            foreach ($banks as $b) {
                if (count($suggestions) >= 12) break;
                $suggestions[$b['bankCode']] = $b;
            }
            $suggestions = array_values($suggestions);
        }

        return response()->json([
            'resolved' => false,
            'suggestions' => $suggestions,
        ]);
    }

    private function rememberRecent(Request $request, array $bank): void
    {
        $recent = (array) $request->session()->get('recent_banks', []);
        $recent = collect([$bank['bankCode'] => $bank] + $recent)->take(8)->all();
        $request->session()->put('recent_banks', $recent);
    }
}
