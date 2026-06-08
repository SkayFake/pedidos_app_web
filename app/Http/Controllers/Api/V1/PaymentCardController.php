<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PaymentCard;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentCardController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $cards = auth()->user()
            ->paymentCards()
            ->orderBy('created_at', 'desc')
            ->get();

        // Safe transformation to return only what the client needs, decrypting only holder/expiry
        $formattedCards = $cards->map(function ($card) {
            return [
                'id' => $card->id,
                'card_holder' => $card->card_holder,
                'expiry_date' => $card->expiry_date,
                'card_type' => $card->card_type,
                'last_four' => $card->last_four,
            ];
        });

        return $this->success($formattedCards, 'Listado de tarjetas.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'card_number' => ['required', 'string', 'size:16', 'regex:/^\d+$/'],
            'card_holder' => ['required', 'string', 'max:100'],
            'expiry_date' => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'],
            'card_type' => ['required', 'string', 'max:50'],
        ]);

        $lastFour = substr($validated['card_number'], -4);

        $card = auth()->user()->paymentCards()->create([
            'card_number' => $validated['card_number'],
            'card_holder' => $validated['card_holder'],
            'expiry_date' => $validated['expiry_date'],
            'card_type' => $validated['card_type'],
            'last_four' => $lastFour,
        ]);

        $responseData = [
            'id' => $card->id,
            'card_holder' => $card->card_holder,
            'expiry_date' => $card->expiry_date,
            'card_type' => $card->card_type,
            'last_four' => $card->last_four,
        ];

        return $this->success($responseData, 'Tarjeta agregada exitosamente.', 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $card = auth()->user()->paymentCards()->findOrFail($id);
        $card->delete();

        return $this->success(null, 'Tarjeta eliminada exitosamente.');
    }
}
