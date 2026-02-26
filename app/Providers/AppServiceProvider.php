<?php

namespace App\Providers;

use App\Repositories\ContractRepository;
use App\Repositories\ContractRepositoryInterface;
use App\Repositories\InvoiceRepository;
use App\Repositories\InvoiceRepositoryInterface;
use App\Repositories\PaymentRepository;
use App\Repositories\PaymentRepositoryInterface;
use App\Services\Tax\MunicipalFeeTaxCalculator;
use App\Services\Tax\TaxService;
use App\Services\Tax\VatTaxCalculator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ContractRepositoryInterface::class, ContractRepository::class);
        $this->app->bind(InvoiceRepositoryInterface::class, InvoiceRepository::class);
        $this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);

        $this->app->when(TaxService::class)->needs('$calculators')->give(function () {
            return [
                $this->app->make(VatTaxCalculator::class),
                $this->app->make(MunicipalFeeTaxCalculator::class),
            ];
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
