<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td style="padding-bottom:8px;">
      <strong>{{ config('mail_brand.brand_name') }}</strong>
    </td>
  </tr>
  <tr>
    <td style="padding-bottom:6px; color:{{ config('mail_brand.footer_text') }};">
      Need help? Contact us at <a href="mailto:{{ config('mail_brand.contact.email') }}">{{ config('mail_brand.contact.email') }}</a>
    </td>
  </tr>
  @if(config('mail_brand.contact.address'))
  <tr>
    <td style="padding-bottom:10px; color:{{ config('mail_brand.footer_text') }};">
      {{ config('mail_brand.contact.address') }}
    </td>
  </tr>
  @endif
  <tr>
    <td>
      @php($s = config('mail_brand.socials'))
      @if($s['twitter'] || $s['linkedin'] || $s['facebook'] || $s['instagram'])
        <span style="margin-right:10px;">Follow us:</span>
        @if($s['twitter'])<a href="{{ $s['twitter'] }}" target="_blank">Twitter</a>@endif
        @if($s['linkedin'])<span> · </span><a href="{{ $s['linkedin'] }}" target="_blank">LinkedIn</a>@endif
        @if($s['facebook'])<span> · </span><a href="{{ $s['facebook'] }}" target="_blank">Facebook</a>@endif
        @if($s['instagram'])<span> · </span><a href="{{ $s['instagram'] }}" target="_blank">Instagram</a>@endif
      @endif
    </td>
  </tr>
</table>
