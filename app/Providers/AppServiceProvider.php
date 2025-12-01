<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Payment;
use App\Observers\PaymentObserver;
use App\Models\Invoice;
use App\Observers\InvoiceObserver;
use App\Models\InvoiceItem;
use App\Observers\InvoiceItemObserver;
use App\Models\FormControlPeople;
use App\Observers\FormControlPeopleObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Payment::observe(PaymentObserver::class);
        Invoice::observe(InvoiceObserver::class);
        InvoiceItem::observe(InvoiceItemObserver::class);
        FormControlPeople::observe(FormControlPeopleObserver::class);
        // $this->loadTranslationsFrom(__DIR__.'/../lang', 'general');
    }
}
