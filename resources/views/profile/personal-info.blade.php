@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4 mb-3">Personal Information</h1>

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

    <form method="POST" action="{{ route('profile.personal.update') }}" enctype="multipart/form-data" class="card p-3">
        @csrf
        <div class="mb-3">
            <label class="form-label">Full name</label>
            <input type="text" name="full_name" value="{{ old('full_name', $user->full_name) }}" class="form-control" />
        </div>
        <div class="mb-3">
            <label class="form-label">Notification email</label>
            <input type="email" name="notification_email" value="{{ old('notification_email', $user->notification_email) }}" class="form-control" />
        </div>
        <div class="mb-3">
            <label class="form-label">Avatar</label>
            <input type="file" name="avatar" class="form-control" accept="image/*" />
            @if ($user->avatar_path)
                <div class="mt-2">
                    <img src="{{ asset('storage/' . $user->avatar_path) }}" alt="Avatar" style="height:64px;border-radius:8px;" />
                </div>
            @endif
        </div>
        <div>
            <button type="submit" class="btn btn-primary">Save changes</button>
            <a href="{{ route('profile.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
