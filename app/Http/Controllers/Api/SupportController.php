<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SupportController extends Controller
{
    public function help(Request $request)
    {
        // Static help topics; can be moved to DB later
        return response()->json([
            'topics' => [
                ['id' => 'getting-started', 'title' => 'Getting Started', 'url' => url('/support/help#getting-started')],
                ['id' => 'kyc', 'title' => 'KYC Verification', 'url' => url('/support/help#kyc')],
                ['id' => 'transfers', 'title' => 'Transfers', 'url' => url('/support/help#transfers')],
                ['id' => 'fees', 'title' => 'Fees & Rates', 'url' => url('/support/help#fees')],
                ['id' => 'security', 'title' => 'Security & PIN', 'url' => url('/support/help#security')],
            ],
        ]);
    }

    public function contact(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required','string','max:190'],
            'message' => ['required','string','max:4000'],
        ]);
        $u = $request->user();
        $t = SupportTicket::create([
            'user_id' => $u->id,
            'subject' => $data['subject'],
            'message' => $data['message'],
            'status' => 'open',
            'priority' => 'normal',
            'admin_reply_count' => 0,
        ]);
        // seed message thread
        $key = $this->threadKey($t->id);
        Cache::put($key, [[
            'from' => 'user',
            'body' => $data['message'],
            'at' => now()->toIso8601String(),
        ]], now()->addYear());
        return response()->json(['success' => true, 'ticket' => [
            'id' => $t->id,
            'subject' => $t->subject,
            'status' => $t->status,
            'createdAt' => $t->created_at?->toIso8601String(),
        ]]);
    }

    public function index(Request $request)
    {
        $u = $request->user();
        $tickets = SupportTicket::query()->where('user_id', $u->id)->orderByDesc('id')->paginate(15);
        $data = array_map(function ($t) {
            return [
                'id' => $t->id,
                'subject' => $t->subject,
                'status' => $t->status,
                'priority' => $t->priority,
                'createdAt' => $t->created_at?->toIso8601String(),
                'lastReplyAt' => $t->last_reply_at?->toIso8601String(),
            ];
        }, $tickets->items());
        return response()->json(['data' => $data, 'meta' => [
            'page' => $tickets->currentPage(),
            'perPage' => $tickets->perPage(),
            'total' => $tickets->total(),
            'lastPage' => $tickets->lastPage(),
        ]]);
    }

    public function show(Request $request, SupportTicket $ticket)
    {
        $this->authorizeOwner($ticket);
        $msgs = Cache::get($this->threadKey($ticket->id), []);
        return response()->json([
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'createdAt' => $ticket->created_at?->toIso8601String(),
            'messages' => $msgs,
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $this->authorizeOwner($ticket);
        $data = $request->validate(['message' => ['required','string','max:4000']]);
        $key = $this->threadKey($ticket->id);
        $msgs = Cache::get($key, []);
        $msgs[] = ['from' => 'user', 'body' => $data['message'], 'at' => now()->toIso8601String()];
        Cache::put($key, $msgs, now()->addYear());
        $ticket->last_reply_at = now();
        $ticket->save();
        return response()->json(['success' => true, 'messages' => $msgs]);
    }

    protected function authorizeOwner(SupportTicket $ticket): void
    {
        $userId = Auth::id();
        abort_if($ticket->user_id !== $userId, 403);
    }

    protected function threadKey(int $ticketId): string
    {
        return 'support:thread:' . $ticketId;
    }
}
