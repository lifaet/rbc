<?php

namespace App\Console\Commands;

use App\Models\Cycle;
use App\Models\Factory;
use App\Models\ReminderLog;
use App\Models\Submission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendSurveyReminders extends Command
{
    protected $signature = 'reminders:send';
    protected $description = 'Send reminder emails to factories that have not submitted for an active/closing cycle';

    // Days-before-close offsets at which a reminder should fire.
    protected array $offsets = [14, 3, 0];

    public function handle(): int
    {
        $cycles = Cycle::where('status', 'open')->get();

        foreach ($cycles as $cycle) {
            $daysToClose = now()->startOfDay()->diffInDays($cycle->close_date, false);

            if (! in_array($daysToClose, $this->offsets)) {
                continue;
            }

            $type = $daysToClose === 0 ? 'on_close' : $daysToClose.'_days_before';

            $submittedFactoryIds = Submission::where('cycle_id', $cycle->id)
                ->where('status', 'submitted')
                ->pluck('factory_id');

            $pendingFactories = Factory::active()
                ->whereNotIn('id', $submittedFactoryIds)
                ->whereHas('users') // only factories with a login to notify
                ->get();

            foreach ($pendingFactories as $factory) {
                $alreadySent = ReminderLog::where([
                    'factory_id' => $factory->id,
                    'cycle_id' => $cycle->id,
                    'type' => $type,
                ])->exists();

                if ($alreadySent || ! $factory->contact_email) {
                    continue;
                }

                Mail::raw(
                    "Dear {$factory->name},\n\nThis is a reminder that the sustainability survey for cycle \"{$cycle->name}\" is due by {$cycle->close_date->format('d M Y')}. Please log in and complete your submission.\n\nThank you.",
                    function ($message) use ($factory, $cycle) {
                        $message->to($factory->contact_email)
                            ->subject("Reminder: Sustainability Survey Due — {$cycle->name}");
                    }
                );

                ReminderLog::create([
                    'factory_id' => $factory->id,
                    'cycle_id' => $cycle->id,
                    'type' => $type,
                    'sent_at' => now(),
                ]);

                $this->info("Reminder sent to {$factory->name} ({$type})");
            }
        }

        return self::SUCCESS;
    }
}
