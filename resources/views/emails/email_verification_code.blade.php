@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
<img src="{{ asset('img/app-logo.png') }}" width="200">
@endcomponent
@endslot

<h2>
@lang("Welcome To WyCoin!")
</h2>

@lang("Here's your verification code.")

<h1> <span style="border-radius: 0.5rem; background-color:#2d3748; color: white;">{{ $code }}</span></h1>

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
<p style="color:black">@lang("This message has been sent automatically. Please do not reply.")</p>
© {{ date('Y') }} {{ config('app.name') }}
@endcomponent
@endslot
@endcomponent
