<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $notification->title ?? config('mail_brand.brand_name') }}</title>
    <style>
        body { margin:0; padding:0; background:#f2f4f7; }
        .wrapper { width:100%; table-layout:fixed; background:#f2f4f7; padding:20px 0; }
        .main { background:#ffffff; margin:0 auto; width:100%; max-width:640px; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(16,24,40,.1); }
        .brand { background:{{ config('mail_brand.primary') }}; color:#fff; padding:24px; text-align:center; }
        .brand h1 { margin:0; font:600 20px/1.2 -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial; }
        .brand img { max-height:32px; display:block; margin:0 auto 8px; }
        .preheader { display:none!important; visibility:hidden; opacity:0; color:transparent; height:0; width:0; }
        .content { padding:28px 28px 10px; color:#101828; font:400 16px/1.6 -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial; }
        .title { margin:0 0 6px; font:700 20px/1.3 -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial; color:#0f172a; }
        .amount { font:700 26px/1.2 -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial; margin:6px 0 8px; color:#0f172a; }
        .kv { width:100%; border-collapse:collapse; margin-top:8px; }
        .kv th, .kv td { text-align:left; padding:8px 0; border-bottom:1px solid #e5e7eb; font-size:14px; }
        .kv th { width:46%; color:#475467; font-weight:600; }
        .panel { background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:16px 18px; margin:16px 0; }
        .cta { padding:0 28px 28px; }
        .btn { display:inline-block; background:{{ config('mail_brand.accent') }}; color:#fff !important; text-decoration:none; padding:12px 20px; border-radius:8px; font:600 14px/1 -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial; }
        .footer { padding:18px 28px 26px; color:#e5e7eb; background:{{ config('mail_brand.footer_bg') }}; font:400 12px/1.5 -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial; }
        .footer a { color:{{ config('mail_brand.footer_text') }}; text-decoration:none; }
        @media (max-width: 480px) { .content, .cta, .footer { padding-left:16px; padding-right:16px; } }
    </style>
</head>
<body>
    <span class="preheader">{{ $notification->title ?? '' }}</span>
    <table role="presentation" class="wrapper" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td align="center">
                <table role="presentation" class="main" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="brand">
                            @if(config('mail_brand.logo_url'))
                                <img src="{{ config('mail_brand.logo_url') }}" alt="{{ config('mail_brand.brand_name') }}" />
                            @endif
                            <h1>{{ config('mail_brand.brand_name') }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="content">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td class="cta">
                            @yield('cta')
                        </td>
                    </tr>
                    <tr>
                        <td class="footer">
                            @include('emails.partials.footer')
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
