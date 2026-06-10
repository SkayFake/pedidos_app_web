@extends('emails.layout')

@section('title', 'Recuperación de Contraseña')

@section('content')
    <p class="greeting">¡Hola!</p>

    <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en <strong>{{ config('app.name') }}</strong>.</p>

    <p>Usa el siguiente código para completar el proceso:</p>

    <div class="otp-container">
        <div class="otp-code">{{ $otp }}</div>
        <br>
        <span class="otp-expiry">⏱ Expira en 15 minutos</span>
    </div>

    <div class="info-box">
        <p>🔒 Si no solicitaste este cambio, puedes ignorar este correo con total seguridad. Tu contraseña no será modificada.</p>
    </div>

    <p>Si tienes algún problema, no dudes en contactarnos.</p>

    <p style="margin-top: 24px;">
        Saludos,<br>
        <strong>El equipo de {{ config('app.name') }}</strong>
    </p>
@endsection
