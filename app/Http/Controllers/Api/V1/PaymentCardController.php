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

        $formattedCards = [];
        foreach ($cards as $card) {
            try {
                $formattedCards[] = [
                    'id' => $card->id,
                    'card_type' => $card->card_type,
                    'last_four' => $card->last_four,
                    'provider_token' => $card->provider_token,
                ];
            } catch (\Exception $e) {
                \Log::warning("Corrupted payment card ID {$card->id} for user " . auth()->id() . ": " . $e->getMessage());
            }
        }

        return $this->success($formattedCards, 'Listado de tarjetas.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'last_four' => ['required', 'string', 'size:4', 'regex:/^\d+$/'],
            'card_type' => ['required', 'string', 'max:50'],
            'provider_token' => ['required', 'string'],
        ]);

        $card = auth()->user()->paymentCards()->create([
            'card_type' => $validated['card_type'],
            'last_four' => $validated['last_four'],
            'provider_token' => $validated['provider_token'],
        ]);

        $responseData = [
            'id' => $card->id,
            'card_type' => $card->card_type,
            'last_four' => $card->last_four,
            'provider_token' => $card->provider_token,
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
