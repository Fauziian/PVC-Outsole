<?php

use App\Providers\AppServiceProvider;
use Illuminate\Support\DefaultProviders;

return [
    ...(new DefaultProviders)->toArray(),
    AppServiceProvider::class,
];
