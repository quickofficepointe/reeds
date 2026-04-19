<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reward;
use App\Services\AdvantaSMSService;
use Illuminate\Support\Facades\Log;

class GenerateDailyReward extends Command
{
    protected $signature = 'rewards:generate-daily';
    protected $description = 'Generate security reward for tomorrow';

    public function handle()
    {
        try {
            $existing = Reward::whereDate('reward_date', now()->addDay())->first();

            if ($existing) {
                $this->info('Reward already exists for tomorrow.');
                return;
            }

            $reward = Reward::createTomorrowReward();
            $this->sendRewardSms($reward);

            $this->info('Security reward generated for ' . now()->addDay()->format('Y-m-d'));
            $this->info('Employee: ' . $reward->employee->formal_name);
            $this->info('Amount: ' . $reward->formatted_amount);

        } catch (\Exception $e) {
            $this->error('Failed: ' . $e->getMessage());
            Log::error('Daily reward failed: ' . $e->getMessage());
        }
    }

    private function sendRewardSms(Reward $reward)
    {
        $employee = $reward->employee;
        if (!$employee->phone) return;

        $smsService = new AdvantaSMSService();
        $formattedDate = $reward->reward_date->format('l, F jS, Y');

        $message = "SECURITY REWARD ALERT\n\n" .
                   "Hello {$employee->first_name},\n\n" .
                   "You have been awarded 200 KES.\n" .
                   "Your meal card will be worth 200 KES today ({$formattedDate}).\n\n" .
                   "Present your card at the canteen as usual.\n" .
                   "Valid only TODAY.\n\n" .
                   "- Reeds Africa Management";

        $response = $smsService->sendSMS($employee->phone, $message, $reward->id);

        if (isset($response['responses'][0]['respose-code']) && $response['responses'][0]['respose-code'] == 200) {
            $reward->update([
                'sms_sent' => true,
                'sms_sent_at' => now(),
                'sms_message_id' => $response['responses'][0]['messageid'] ?? null,
                'sms_status' => 'sent'
            ]);
        } else {
            $error = $response['responses'][0]['response-description'] ?? 'Unknown error';
            $reward->update([
                'sms_sent' => false,
                'sms_status' => 'failed',
                'sms_error' => $error
            ]);
        }
    }
}
