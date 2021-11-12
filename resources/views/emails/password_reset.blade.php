@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header', ['url' => config('app.url')])
            <img src="{{ asset('img/logo.png') }}" class="logo">
        @endcomponent
    @endslot

    {{-- Greeting --}}
    # @lang('Welcome!')

    {{-- Intro Lines --}}
    # @lang("You are receiving this email because we received a password reset request for your account.")

    {{-- Action Button --}}
    @component('mail::button', ['url' => $url, 'color' => 'primary'])
        # @lang("Reset Password")
    @endcomponent

    {{-- Outro Lines --}}
    # @lang("This link will expire in 60 minutes.")
    # @lang("If you did not request a password reset, no further action is required.")

    @component('mail::subcopy')
        @lang(
        "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\n".
        'into your web browser:',
        [
        'actionText' => "Reset Password",
        ]
        ) <span class="break-all">[{{ $url }}]({{ $url }})</span>
    @endcomponent

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            @lang("This message has been sent automatically. Please do not reply.")
            © {{ date('Y') }} {{ config('app.name') }}
        @endcomponent
    @endslot
@endcomponent
