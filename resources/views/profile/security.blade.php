@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4 mb-3">Security Settings</h1>

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

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card p-3 h-100">
                <h5 class="mb-3">Login & Device</h5>
                <form method="POST" action="{{ route('profile.security.toggles') }}">
                    @csrf
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="sms_login_enabled" name="sms_login_enabled" value="1" {{ $settings->sms_login_enabled ? 'checked' : '' }}>
                        <label class="form-check-label" for="sms_login_enabled">SMS login (receive OTP by SMS)</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="face_id_enabled" name="face_id_enabled" value="1" {{ $settings->face_id_enabled ? 'checked' : '' }}>
                        <label class="form-check-label" for="face_id_enabled">Face ID / Biometric (device dependent)</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="pin_enabled" name="pin_enabled" value="1" {{ $settings->pin_enabled ? 'checked' : '' }} {{ empty($settings->pin_hash) ? 'disabled' : '' }}>
                        <label class="form-check-label" for="pin_enabled">Require PIN at login</label>
                        @if (empty($settings->pin_hash))
                            <small class="text-muted d-block">Set a PIN below to enable.</small>
                        @endif
                    </div>
                    <button type="submit" class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-3 h-100">
                <h5 class="mb-3">PIN</h5>
                <form method="POST" action="{{ route('profile.security.pin') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">New PIN</label>
                        <input type="password" name="pin" class="form-control" minlength="4" maxlength="6" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm PIN</label>
                        <input type="password" name="pin_confirmation" class="form-control" minlength="4" maxlength="6" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update PIN</button>
                </form>
            </div>
        </div>
    </div>

    <div class="card p-3 mt-4">
        <h5 class="mb-3">Recent Logins</h5>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>IP</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Device</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentLogins as $log)
                        <tr>
                            <td>{{ $log->created_at }}</td>
                            <td>{{ $log->ip_address }}</td>
                            <td>{{ $log->login_method ?? 'password' }}</td>
                            <td><span class="badge bg-{{ $log->status === 'success' ? 'success' : 'danger' }}">{{ $log->status }}</span></td>
                            <td>{{ $log->device_info ?: \Illuminate\Support\Str::limit($log->user_agent, 50) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No recent logins yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
