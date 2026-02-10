<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// ============================================
// INSPIRATION COMMAND (Your existing code)
// ============================================

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ============================================
// INVOICE AUTOMATION SCHEDULES
// ============================================

// Generate bi-weekly invoices every Saturday at 3:00 PM Nairobi Time
// Saturday = 6 (Sunday = 0, Monday = 1, Tuesday = 2, Wednesday = 3, Thursday = 4, Friday = 5, Saturday = 6)
Schedule::command('invoices:generate-biweekly')
    ->weeklyOn(6, '15:00') // Saturday at 3:00 PM
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

// Clear application cache every Sunday at 2:00 AM (Sunday = 0)
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
    // You can implement this if you need manual invoice generation
    $this->info("Generating invoice for vendor {$vendorId} for {$period} period...");
    // Call your invoice generation logic here
})->purpose('Manually generate invoice for a specific vendor');

// Command to test email sending
Artisan::command('test:invoice-email {email}', function ($email) {
    // You can implement this to test invoice emails
    $this->info("Sending test invoice email to {$email}...");
    // Test email logic here
})->purpose('Test invoice email sending to a specific address');

// ============================================
// UTILITY COMMANDS
// ============================================

// Check system health
Artisan::command('system:health', function () {
    $this->info('🔍 Checking system health...');

    // Check database connection
    try {
        \DB::connection()->getPdo();
        $this->info('✅ Database connection: OK');
    } catch (\Exception $e) {
        $this->error('❌ Database connection: FAILED');
    }

    // Check storage permissions
    $storagePath = storage_path();
    if (is_writable($storagePath)) {
        $this->info('✅ Storage permissions: OK');
    } else {
        $this->error('❌ Storage permissions: FAILED');
    }

    // Check if commands are registered
    $commands = ['invoices:generate-biweekly', 'invoices:send-reminders'];
    foreach ($commands as $command) {
        if (\Artisan::has($command)) {
            $this->info("✅ Command '{$command}': REGISTERED");
        } else {
            $this->error("❌ Command '{$command}': NOT REGISTERED");
        }
    }

    // Check next scheduled run
    $schedule = app()->make(\Illuminate\Console\Scheduling\Schedule::class);
    $events = $schedule->events();

    $this->info("\n📅 Next scheduled runs:");
    foreach ($events as $event) {
        $nextRun = $event->nextRunDate()->format('Y-m-d H:i:s');
        $this->info("  • {$event->description}: {$nextRun}");
    }

})->purpose('Check system health and scheduled tasks');
