<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CouponService;

class UpdateCouponExpirationDates extends Command
{
    protected $signature = 'coupons:update-expiration-dates';
    protected $description = 'Aggiorna le date di scadenza dei coupon in base ai periodi di chiusura';

    protected $couponService;

    public function __construct(CouponService $couponService)
    {
        parent::__construct();
        $this->couponService = $couponService;
    }

    public function handle()
    {
        $this->info('Avvio aggiornamento delle date di scadenza dei coupon.');

        try {
            $this->couponService->updateCouponExpirationDates();
            $this->info('Le date di scadenza dei coupon sono state aggiornate con successo.');
        } catch (\Exception $e) {
            $this->error('Si è verificato un errore: ' . $e->getMessage());
        }
    }
}
