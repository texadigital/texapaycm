<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Register • TexaPay</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body{font-family:Inter,system-ui,Arial,sans-serif;background:#0b1220;color:#e6e8ec;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
    .card{background:#0f172a;border:1px solid #1f2a44;border-radius:12px;padding:24px;width:100%;max-width:420px}
    .muted{color:#9aa4b2}
    label{display:block;margin:10px 0 6px}
    input{width:100%;padding:10px;border-radius:8px;border:1px solid #263657;background:#0b1327;color:#e6e8ec}
    .btn{margin-top:14px;width:100%;background:#2563eb;border:none;color:#fff;padding:12px;border-radius:8px;font-weight:600;cursor:pointer}
    a{color:#60a5fa;text-decoration:none}
    .err{color:#fda4af;margin-top:8px}
  </style>
</head>
<body>
  <div class="card">
    <h2 style="margin:0 0 10px">Create your account</h2>
    <p class="muted" style="margin:0 0 18px">Use your phone number. You'll be signed in automatically.</p>
    <form method="post" action="{{ route('register') }}">
      @csrf
      <label for="name">Full name</label>
      <input id="name" name="name" value="{{ old('name') }}" required />

      <label for="phone">Phone number</label>
      <input id="phone" name="phone" value="{{ old('phone') }}" placeholder="e.g. 237653456789" required />

      <label for="password">Password</label>
      <input id="password" name="password" type="password" required />

      <label for="password_confirmation">Confirm password</label>
      <input id="password_confirmation" name="password_confirmation" type="password" required />

      <label for="pin">PIN (4–6 digits)</label>
      <input id="pin" name="pin" inputmode="numeric" pattern="\d{4,6}" required />

      <button class="btn" type="submit">Create account</button>
    </form>
    <p class="muted" style="margin-top:10px">Already have an account? <a href="{{ route('login.show') }}">Sign in</a></p>
    @if ($errors->any())
      <div class="err">{{ $errors->first() }}</div>
    @endif
    @if (session('error'))
      <div class="err">{{ session('error') }}</div>
    @endif
  </div>
</body>
</html>
