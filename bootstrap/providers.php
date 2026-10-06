<?php

return [
    App\Providers\AppServiceProvider::class,
    // Hidrata config('billing.*') desde payment_credentials (admin) antes de
    // que se resuelva el gateway. Debe ir después de AppServiceProvider.
    App\Providers\BillingConfigServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
];
