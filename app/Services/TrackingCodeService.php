<?php

namespace App\Services;

use App\Models\Application;

class TrackingCodeService
{
    public function generate(): string
    {
        $year = now()->year;
        $prefix = "APP-{$year}-";

        $lastCode = Application::where('tracking_code', 'like', "{$prefix}%")
            ->orderByDesc('tracking_code')
            ->value('tracking_code');

        $nextNumber = 1;

        if ($lastCode) {
            $lastNumber = (int) str_replace($prefix, '', $lastCode);
            $nextNumber = $lastNumber + 1;
        }

        return $prefix.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
