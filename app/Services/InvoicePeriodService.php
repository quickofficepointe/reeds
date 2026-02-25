<?php
// app/Services/InvoicePeriodService.php

namespace App\Services;

use Carbon\Carbon;

class InvoicePeriodService
{
    protected $systemStartDate;

    public function __construct()
    {
        // System started on Monday, February 2, 2026
        $this->systemStartDate = Carbon::create(2026, 2, 2);
    }

    /**
     * Get the current bi-weekly period based on system start date
     */
    public function getCurrentPeriod(): array
    {
        $today = Carbon::now();
        return $this->calculatePeriodForDate($today);
    }

    /**
     * Get the previous bi-weekly period
     */
    public function getPreviousPeriod(): array
    {
        $today = Carbon::now();
        $lastPeriodDate = $today->copy()->subDays(14);
        return $this->calculatePeriodForDate($lastPeriodDate);
    }

    /**
     * Get the next bi-weekly period
     */
    public function getNextPeriod(): array
    {
        $today = Carbon::now();
        $nextPeriodDate = $today->copy()->addDays(14);
        return $this->calculatePeriodForDate($nextPeriodDate);
    }

    /**
     * Get all periods for 2026
     */
    public function getAllPeriodsFor2026(): array
    {
        $periods = [];
        $startDate = Carbon::create(2026, 2, 2); // First period start
        $endOfYear = Carbon::create(2026, 12, 31);

        $cycleNumber = 1;
        $currentStart = $startDate->copy();

        while ($currentStart <= $endOfYear) {
            $periodEnd = $currentStart->copy()->addDays(12); // Add 12 days to get to Saturday

            $periods[] = [
                'start' => $currentStart->copy(),
                'end' => $periodEnd->copy(),
                'cycle_number' => $cycleNumber,
                'period_name' => $currentStart->format('M j') . ' - ' . $periodEnd->format('M j, Y'),
                'start_formatted' => $currentStart->format('Y-m-d'),
                'end_formatted' => $periodEnd->format('Y-m-d')
            ];

            $currentStart->addDays(14);
            $cycleNumber++;
        }

        return $periods;
    }

    /**
     * Get a specific period by cycle number
     */
    public function getPeriodByCycle(int $cycleNumber): ?array
    {
        $daysToAdd = ($cycleNumber - 1) * 14;
        $periodStart = $this->systemStartDate->copy()->addDays($daysToAdd);
        $periodEnd = $periodStart->copy()->addDays(12);

        return [
            'start' => $periodStart,
            'end' => $periodEnd,
            'cycle_number' => $cycleNumber,
            'period_name' => $periodStart->format('M j') . ' - ' . $periodEnd->format('M j, Y')
        ];
    }

    /**
     * Calculate the bi-weekly period for a given date
     */
    public function calculatePeriodForDate(Carbon $date): array
    {
        // Calculate days since system start
        $daysSinceStart = $this->systemStartDate->diffInDays($date);

        // Calculate which cycle (14-day cycles)
        $cycleNumber = floor($daysSinceStart / 14) + 1;

        // Calculate period start (Monday)
        $periodStart = $this->systemStartDate->copy()->addDays(($cycleNumber - 1) * 14);

        // Calculate period end (Saturday)
        $periodEnd = $periodStart->copy()->addDays(13); // Add 12 days to get to Saturday

        return [
            'start' => $periodStart,
            'end' => $periodEnd,
            'cycle_number' => $cycleNumber,
            'period_name' => $periodStart->format('M j') . ' - ' . $periodEnd->format('M j, Y'),
            'is_current' => $this->isCurrentPeriod($periodStart, $periodEnd)
        ];
    }

    /**
     * Check if a date falls within a given period
     */
    public function dateInPeriod(Carbon $date, Carbon $periodStart, Carbon $periodEnd): bool
    {
        return $date->between($periodStart, $periodEnd);
    }

    /**
     * Check if period is current
     */
    protected function isCurrentPeriod(Carbon $periodStart, Carbon $periodEnd): bool
    {
        $today = Carbon::now();
        return $today->between($periodStart, $periodEnd);
    }

    /**
     * Get period status (past, current, future)
     */
    public function getPeriodStatus(Carbon $periodEnd): string
    {
        $today = Carbon::now();

        if ($periodEnd->lt($today)) {
            return 'past';
        } elseif ($periodEnd->gte($today) && $periodEnd->lte($today->copy()->addDays(14))) {
            return 'current';
        } else {
            return 'future';
        }
    }

    /**
     * Generate invoice number with period info
     */
    public function generateInvoiceNumber(int $vendorId, Carbon $periodEnd, int $sequence): string
    {
        $year = $periodEnd->format('Y');
        $month = $periodEnd->format('m');
        $vendorPadded = str_pad($vendorId, 4, '0', STR_PAD_LEFT);
        $sequencePadded = str_pad($sequence, 3, '0', STR_PAD_LEFT);

        // Get period cycle
        $period = $this->calculatePeriodForDate($periodEnd);
        $cycle = str_pad($period['cycle_number'], 2, '0', STR_PAD_LEFT);

        return "INV-{$year}{$month}-{$vendorPadded}-C{$cycle}-{$sequencePadded}";
    }

    /**
     * Get all periods in a given year
     */
    public function getPeriodsInYear(int $year): array
    {
        $periods = [];
        $currentDate = Carbon::create($year, 1, 1);
        $endOfYear = Carbon::create($year, 12, 31);

        while ($currentDate <= $endOfYear) {
            $period = $this->calculatePeriodForDate($currentDate);

            $periodKey = $period['start']->format('Y-m-d');
            if (!isset($periods[$periodKey])) {
                $periods[$periodKey] = $period;
            }

            $currentDate->addDays(14);
        }

        return array_values($periods);
    }
}
