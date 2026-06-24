<?php

namespace App\Console\Commands;

use App\Services\CouponValidationService;
use Illuminate\Console\Command;

class CleanupExpiredCoupons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coupons:cleanup-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove expired coupons from user accounts';

    /**
     * Execute the console command.
     */
    public function handle(CouponValidationService $couponService)
    {
        $this->info('Starting coupon cleanup...');

        $cleaned = $couponService->cleanupExpiredCoupons();

        $this->info("Cleaned up {$cleaned} expired coupons from user accounts.");
    }
}
