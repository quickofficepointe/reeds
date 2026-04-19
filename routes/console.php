<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Reward;

// ============================================
// INSPIRATION COMMAND
// ============================================
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ============================================
// INVOICE AUTOMATION SCHEDULES
// ============================================

// Generate bi-weekly invoices every Saturday at 3:00 PM Nairobi Time
Schedule::command('invoices:generate-biweekly')
    ->weeklyOn(6, '15:00')
    ->timezone('Africa/Nairobi')
    ->appendOutputTo(storage_path('logs/invoices/biweekly-generation.log'))
    ->description('Generate bi-weekly vendor invoices');

// Send invoice reminders daily at 10:00 AM Nairobi Time
Schedule::command('invoices:send-reminders')
    ->dailyAt('10:00')
    ->timezone('Africa/Nairobi')
    ->appendOutputTo(storage_path('logs/invoices/reminders.log'))
    ->description('Send invoice payment reminders');

// ============================================
// SECURITY REWARDS SCHEDULES
// ============================================

// Generate next day's security reward at 10:00 PM daily
Schedule::command('rewards:generate-daily')
    ->dailyAt('22:00')
    ->timezone('Africa/Nairobi')
    ->appendOutputTo(storage_path('logs/rewards/generation.log'))
    ->description('Generate daily 200 KES security reward for tomorrow');

// Expire unclaimed rewards at 12:05 AM daily
Schedule::call(function () {
    Reward::where('status', 'pending')
        ->where('reward_date', '<', today())
        ->update(['status' => 'expired']);
})->dailyAt('00:05')
  ->timezone('Africa/Nairobi')
  ->description('Expire unclaimed security rewards');

// ============================================
// TESTING SCHEDULES (Comment out in production)
// ============================================

// For testing invoice generation (uncomment when testing)
// Schedule::command('invoices:generate-biweekly')
//     ->everyMinute()
//     ->appendOutputTo(storage_path('logs/invoices/test-generation.log'))
//     ->description('Test: Generate invoices every minute');

// For testing reminders (uncomment when testing)
// Schedule::command('invoices:send-reminders')
//     ->everyMinute()
//     ->appendOutputTo(storage_path('logs/invoices/test-reminders.log'))
//     ->description('Test: Send reminders every minute');

// ============================================
// SYSTEM MAINTENANCE SCHEDULES
// ============================================

// Prune old Telescope entries daily at midnight
Schedule::command('telescope:prune --hours=48')
    ->daily()
    ->description('Prune old Telescope entries');

// Clear application cache every Sunday at 2:00 AM
Schedule::command('cache:clear')
    ->weeklyOn(0, '02:00')
    ->description('Clear application cache weekly');

// Backup database daily at 1:00 AM
Schedule::command('backup:clean')
    ->dailyAt('01:00')
    ->description('Clean old backups');

Schedule::command('backup:run')
    ->dailyAt('01:30')
    ->description('Run database backup');

// ============================================
// QR SYSTEM SPECIFIC MAINTENANCE
// ============================================

// Clean up old QR scan logs (keep 90 days only)
Schedule::command('logs:clean --days=90')
    ->dailyAt('03:00')
    ->description('Clean old application logs');

// Generate daily QR scan reports at 11:59 PM
Schedule::command('reports:generate-daily')
    ->dailyAt('23:59')
    ->description('Generate daily QR scan reports');

// ============================================
// CUSTOM ARTISAN COMMANDS (Optional)
// ============================================

// Command to manually trigger invoice generation
Artisan::command('vendor:generate-invoice {vendor_id} {--period=current}', function ($vendorId, $period) {
    $this->info("Generating invoice for vendor {$vendorId} for {$period} period...");
})->purpose('Manually generate invoice for a specific vendor');

// Command to test email sending
Artisan::command('test:invoice-email {email}', function ($email) {
    $this->info("Sending test invoice email to {$email}...");
})->purpose('Test invoice email sending to a specific address');

// ============================================
// UTILITY COMMANDS
// ============================================

// Check system health
Artisan::command('system:health', function () {
    $this->info('Checking system health...');

    try {
        \DB::connection()->getPdo();
        $this->info('[OK] Database connection');
    } catch (\Exception $e) {
        $this->error('[FAILED] Database connection');
    }

    $storagePath = storage_path();
    if (is_writable($storagePath)) {
        $this->info('[OK] Storage permissions');
    } else {
        $this->error('[FAILED] Storage permissions');
    }

    $commands = ['invoices:generate-biweekly', 'invoices:send-reminders', 'rewards:generate-daily'];
    foreach ($commands as $command) {
        if (\Artisan::has($command)) {
            $this->info("[OK] Command '{$command}': REGISTERED");
        } else {
            $this->error("[FAILED] Command '{$command}': NOT REGISTERED");
        }
    }

    $schedule = app()->make(\Illuminate\Console\Scheduling\Schedule::class);
    $events = $schedule->events();

    $this->info("\nNext scheduled runs:");
    foreach ($events as $event) {
        $nextRun = $event->nextRunDate()->format('Y-m-d H:i:s');
        $this->info("  - {$event->description}: {$nextRun}");
    }

})->purpose('Check system health and scheduled tasks');
