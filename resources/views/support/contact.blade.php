@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4 mb-3">Contact Support</h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('support.contact.submit') }}" class="card p-3">
        @csrf
        <div class="mb-3">
            <label class="form-label">Subject</label>
            <input type="text" name="subject" class="form-control" value="{{ old('subject') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Priority</label>
            <select name="priority" class="form-select">
                <option value="normal" {{ old('priority')==='normal' ? 'selected' : '' }}>Normal</option>
                <option value="low" {{ old('priority')==='low' ? 'selected' : '' }}>Low</option>
                <option value="high" {{ old('priority')==='high' ? 'selected' : '' }}>High</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Message</label>
            <textarea name="message" class="form-control" rows="6" required>{{ old('message') }}</textarea>
        </div>
        <div>
            <button type="submit" class="btn btn-primary">Submit Ticket</button>
            <a href="{{ route('support.tickets') }}" class="btn btn-secondary">My Tickets</a>
        </div>
    </form>
</div>
@endsection
