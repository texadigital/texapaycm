@extends('layouts.app')

@section('content')
<div class="container py-5" style="max-width:520px;">
  <h1 class="h4 mb-3">Enter PIN</h1>
  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif
  <div class="card p-4">
    <form method="POST" action="{{ route('login.pin.verify') }}">
      @csrf
      <div class="mb-3">
        <label class="form-label">PIN</label>
        <input type="password" name="pin" class="form-control" minlength="4" maxlength="6" required autofocus>
        <small class="text-muted">Enter your 4–6 digit login PIN.</small>
      </div>
      <button type="submit" class="btn btn-primary">Verify</button>
      <a href="{{ route('login.show') }}" class="btn btn-outline-secondary ms-2">Cancel</a>
    </form>
  </div>
</div>
@endsection
