@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
<img src="{{ asset('img/logo.png') }}" width="200">
@endcomponent
@endslot

{{-- Greeting --}}
@lang('Welcome!')

{{-- Intro Lines --}}
@lang("You are receiving this email because we received a password reset request for your account.")

{{-- Action Button --}}
@component('mail::button', ['url' => $url, 'color' => 'primary'])
@lang("Reset Password")
@endcomponent

{{-- Outro Lines --}}
<p style="color:red">@lang("This link will expire in 60 minutes.")</p>
@lang("If you did not request a password reset, no further action is required.")

@component('mail::subcopy')
@lang("WyCoin Team")
<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td style="padding: 1rem">
@lang(
"If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\n".
'into your web browser:',
[
'actionText' => "Reset Password",
]
)
<span class="break-all">{{ $url }}</span>
</td>
</tr>
</table>
@endcomponent

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
<p style="color:black">@lang("This message has been sent automatically. Please do not reply.")</p>
© {{ date('Y') }} {{ config('app.name') }}
@endcomponent
@endslot
@endcomponent
