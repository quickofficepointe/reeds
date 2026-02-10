<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\VendorController;
use Illuminate\Support\Facades\Log;

class GenerateBiWeeklyInvoices extends Command
{
    protected $signature = 'invoices:generate-biweekly';
    protected $description = 'Generate bi-weekly invoices for all vendors';

    public function handle()
    {
        $this->info('Starting bi-weekly invoice generation...');

        try {
            $vendorController = app()->make(VendorController::class);
            $result = $vendorController->generateBiWeeklyInvoices();

            $this->info("Generated {$result['generated']} invoices.");

            if (!empty($result['errors'])) {
                $this->error("Errors encountered:");
                foreach ($result['errors'] as $error) {
                    $this->error("- $error");
                }
            }

            Log::info('Bi-weekly invoice generation completed', $result);

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            Log::error('Invoice generation failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
