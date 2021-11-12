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

    <a href="{{ $url }}" class="button button-primary" target="_blank" rel="noopener">
        @lang("Reset Password")
    </a>

    {{-- Outro Lines --}}
    @lang("This link will expire in 60 minutes.")
    @lang("If you did not request a password reset, no further action is required.")
    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td>
                @lang(
                "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\n".
                'into your web browser:',
                [
                'actionText' => "Reset Password",
                ]
                ) <span class="break-all">[{{ $url }}]({{ $url }})</span>
            </td>
        </tr>
    </table>

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            @lang("This message has been sent automatically. Please do not reply.") <br />
            © {{ date('Y') }} {{ config('app.name') }}
        @endcomponent
    @endslot
@endcomponent
