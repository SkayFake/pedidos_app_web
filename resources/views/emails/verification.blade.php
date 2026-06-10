@extends('emails.layout')

@section('title', 'Verifica tu Correo Electrónico')

@section('content')
    <p class="greeting">¡Bienvenido a {{ config('app.name') }}!</p>

    <p>Gracias por registrarte. Para activar tu cuenta, ingresa el siguiente código de verificación en la aplicación:</p>

    <div class="otp-container">
        <div class="otp-code">{{ $otp }}</div>
        <br>
        <span class="otp-expiry">⏱ Expira en 15 minutos</span>
    </div>

    <div class="info-box">
        <p>🛡️ Si no creaste una cuenta en {{ config('app.name') }}, ignora este mensaje. Nadie podrá acceder sin este código.</p>
    </div>

    <p>¡Estamos emocionados de tenerte con nosotros!</p>

    <p style="margin-top: 24px;">
        Saludos,<br>
        <strong>El equipo de {{ config('app.name') }}</strong>
    </p>
@endsection
