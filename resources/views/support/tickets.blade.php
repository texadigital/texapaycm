@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4 mb-3">My Support Tickets</h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="mb-3">
        <a href="{{ route('support.contact') }}" class="btn btn-primary">Open New Ticket</a>
        <a href="{{ route('support.help') }}" class="btn btn-outline-secondary">Help Center</a>
    </div>

    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Updated</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tickets as $ticket)
                    <tr>
                        <td>{{ $ticket->id }}</td>
                        <td>{{ $ticket->subject }}</td>
                        <td><span class="badge bg-{{ $ticket->status === 'closed' ? 'secondary' : ($ticket->status === 'pending' ? 'warning' : 'success') }}">{{ $ticket->status }}</span></td>
                        <td>{{ ucfirst($ticket->priority) }}</td>
                        <td>{{ $ticket->updated_at }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">No tickets yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $tickets->links() }}
    </div>
</div>
@endsection
