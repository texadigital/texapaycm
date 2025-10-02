@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4 mb-3">Notification Preferences</h1>

    @if (empty($user->notification_email))
        <div class="alert alert-warning d-flex align-items-center justify-content-between">
            <div>
                <strong>Action recommended:</strong> Add a notification email so you can receive email notifications. You can set a dedicated email address different from your login email.
            </div>
            <a href="{{ route('profile.personal') }}" class="btn btn-sm btn-outline-primary ms-3">Set notification email</a>
        </div>
    @endif

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

    <form method="POST" action="{{ route('profile.notifications.update') }}" class="card p-3">
        @csrf
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch" id="email_notifications" name="email_notifications" value="1" {{ old('email_notifications', $user->email_notifications ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="email_notifications">Email notifications</label>
        </div>
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch" id="sms_notifications" name="sms_notifications" value="1" {{ old('sms_notifications', $user->sms_notifications ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="sms_notifications">SMS notifications</label>
        </div>
        <div>
            <button type="submit" class="btn btn-primary">Save preferences</button>
            <a href="{{ route('profile.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
