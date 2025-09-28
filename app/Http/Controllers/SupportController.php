<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function help()
    {
        $faqs = Faq::query()->where('is_active', true)->orderBy('category')->orderBy('id')->get();
        return view('support.help', compact('faqs'));
    }

    public function contact()
    {
        return view('support.contact');
    }

    public function submitTicket(Request $request)
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:190'],
            'message' => ['required', 'string'],
            'priority' => ['nullable', 'in:low,normal,high'],
        ]);

        $ticket = SupportTicket::create([
            'user_id' => $request->user()->id,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'priority' => $validated['priority'] ?? 'normal',
        ]);

        return redirect()->route('support.tickets')->with('success', 'Ticket submitted.');
    }

    public function myTickets(Request $request)
    {
        $tickets = SupportTicket::where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->paginate(10);
        return view('support.tickets', compact('tickets'));
    }
}
