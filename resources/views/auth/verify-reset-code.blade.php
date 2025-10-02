<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Reset Code - TexaPay</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-blue-100">
                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Verify reset code
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Enter the 6-digit code sent to your phone to continue
            </p>
        </div>

        @if (session('status'))
            <div class="rounded-md bg-green-50 p-4 text-green-700 text-sm">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-md bg-red-50 p-4 text-red-700 text-sm">
                <ul class="list-disc ml-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="mt-8 space-y-6" action="{{ route('password.verify.submit') }}" method="POST">
            @csrf

            <div>
                <label for="code" class="block text-sm font-medium text-gray-700">
                    Reset Code
                </label>
                <div class="mt-1">
                    <input id="code" name="code" type="text" maxlength="6" required
                           class="appearance-none rounded-md relative block w-full px-3 py-2 border @error('code') border-red-300 @else border-gray-300 @enderror placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm text-center tracking-widest"
                           placeholder="000000">
                </div>
                @error('code')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between gap-3">
                <button type="submit"
                        class="flex-1 group relative flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                    Verify Code
                </button>
                <form id="resendForm" action="{{ route('password.resend') }}" method="POST" class="contents">
                    @csrf
                    <button id="resendBtn" type="submit" {{ ($resend_wait ?? 0) > 0 ? 'disabled' : '' }}
                            class="px-4 py-2 text-sm font-medium rounded-md border bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span id="resendText">
                            {{ ($resend_wait ?? 0) > 0 ? 'Resend in ' . $resend_wait . 's' : 'Resend Code' }}
                        </span>
                    </button>
                </form>
            </div>

            @error('resend')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div class="text-center">
                <a href="{{ route('password.forgot') }}" class="font-medium text-blue-600 hover:text-blue-500">
                    ← Back
                </a>
            </div>
        </form>

        <script>
            (function(){
                var wait = {{ (int) ($resend_wait ?? 0) }};
                var btn = document.getElementById('resendBtn');
                var text = document.getElementById('resendText');
                if (!btn || !text) return;
                if (wait > 0) {
                    btn.disabled = true;
                    var iv = setInterval(function(){
                        wait -= 1;
                        if (wait <= 0) {
                            clearInterval(iv);
                            btn.disabled = false;
                            text.textContent = 'Resend Code';
                        } else {
                            text.textContent = 'Resend in ' + wait + 's';
                        }
                    }, 1000);
                }
            })();
        </script>
    </div>
</body>
</html>
