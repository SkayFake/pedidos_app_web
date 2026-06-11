<!DOCTYPE html>
<html>
<head>
    <title>¡Tienes un nuevo cupón!</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
        .container { background-color: #ffffff; padding: 30px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .coupon-box { background-color: #ffedd5; border: 2px dashed #f97316; padding: 20px; text-align: center; font-size: 24px; font-weight: bold; color: #c2410c; margin: 20px 0; border-radius: 8px; letter-spacing: 2px; }
        .message { color: #555; line-height: 1.6; }
        .footer { margin-top: 30px; font-size: 12px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h1>¡Hola, {{ $coupon->user->name ?? 'Cliente' }}!</h1>
        
        <p class="message">
            @if($customMessage)
                {{ $customMessage }}
            @else
                ¡Felicidades! Has recibido un nuevo cupón de descuento para usar en tu próximo pedido.
            @endif
        </p>

        <div class="coupon-box">
            {{ $coupon->code }}
        </div>

        <p class="message">
            <strong>Beneficio:</strong> 
            @if($coupon->type === 'percent')
                {{ $coupon->value }}% de descuento
            @elseif($coupon->type === 'fixed')
                ${{ $coupon->value }} de descuento
            @elseif($coupon->type === 'free_delivery')
                Envío Gratis
            @endif
        </p>

        @if($coupon->min_order_amount > 0)
        <p class="message"><small>Aplica en pedidos mayores a ${{ $coupon->min_order_amount }}</small></p>
        @endif

        <p class="message">¡Esperamos que lo disfrutes!</p>

        <div class="footer">
            Este es un correo generado automáticamente. Por favor no respondas a este mensaje.
        </div>
    </div>
</body>
</html>
