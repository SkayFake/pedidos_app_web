<?php

namespace App\Jobs;

use App\Mail\CouponReceivedMail;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendIncentiveCouponJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Coupon $templateCoupon,
        public string $customMessage = ''
    ) {}

    public function handle(): void
    {
        // 1. Clonar el cupón plantilla
        $uniqueCode = strtoupper(Str::random(8)) . '-' . $this->user->id;
        
        // Asegurar unicidad del código
        while (Coupon::where('code', $uniqueCode)->exists()) {
            $uniqueCode = strtoupper(Str::random(8)) . '-' . $this->user->id;
        }

        $clonedCoupon = $this->templateCoupon->replicate();
        $clonedCoupon->code = $uniqueCode;
        $clonedCoupon->user_id = $this->user->id;
        $clonedCoupon->is_template = false;
        $clonedCoupon->parent_coupon_id = $this->templateCoupon->id;
        $clonedCoupon->used_count = 0;
        
        $clonedCoupon->save();

        // 2. Enviar el correo
        Mail::to($this->user->email)->send(new CouponReceivedMail($clonedCoupon, $this->customMessage));
    }
}
