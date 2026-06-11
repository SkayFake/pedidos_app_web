<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\RewardMilestone;
use App\Models\UserMilestone;
use App\Jobs\SendIncentiveCouponJob;

class SyncMilestonesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loyalty:sync-milestones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evalúa retroactivamente a todos los usuarios contra los hitos de fidelidad activos y les envía las recompensas que les falten.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronización de hitos de fidelidad...');

        $milestones = RewardMilestone::with('coupon')->where('is_active', true)->orderBy('points_required', 'asc')->get();

        if ($milestones->isEmpty()) {
            $this->warn('No hay hitos activos configurados.');
            return;
        }

        $users = User::where('lifetime_points', '>', 0)->get();
        $count = 0;

        foreach ($users as $user) {
            foreach ($milestones as $milestone) {
                if ($user->lifetime_points >= $milestone->points_required) {
                    $alreadyAchieved = UserMilestone::where('user_id', $user->id)
                        ->where('milestone_id', $milestone->id)
                        ->exists();

                    if (!$alreadyAchieved) {
                        UserMilestone::create([
                            'user_id' => $user->id,
                            'milestone_id' => $milestone->id,
                            'achieved_at' => now(),
                        ]);

                        if ($milestone->coupon) {
                            $message = "¡Has alcanzado los {$milestone->points_required} puntos de fidelidad! Aquí tienes tu recompensa.";
                            SendIncentiveCouponJob::dispatch($user, $milestone->coupon, $message);
                        }

                        $this->line("Otorgando hito de {$milestone->points_required} puntos al usuario: {$user->email}");
                        $count++;
                    }
                }
            }
        }

        $this->info("¡Sincronización completada! Se entregaron {$count} recompensas retroactivas.");
    }
}
