<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\SupportTicketSubmitted;

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

        // Email notifications
        try {
            $supportEmail = config('mail.support_address', env('SUPPORT_EMAIL', 'support@texa.ng'));
            Mail::to($supportEmail)->send(new SupportTicketSubmitted($ticket));
            // Acknowledgement to user (if email available)
            if ($request->user()->email) {
                Mail::to($request->user()->email)->send(new SupportTicketSubmitted($ticket));
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to send support ticket emails', ['error' => $e->getMessage()]);
        }

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
