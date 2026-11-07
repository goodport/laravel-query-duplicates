<?php

use Goodport\QueryDuplicates\Config\AdminRouteDefaults;
use Goodport\QueryDuplicates\Http\QueryDuplicateController;
use Illuminate\Support\Facades\Route;

$adminRoute = AdminRouteDefaults::normalize(config('query-duplicates.admin_route'));

Route::middleware($adminRoute['middleware'])
    ->prefix($adminRoute['prefix'])
    ->name('query-duplicates.')
    ->group(function (): void {
        Route::get('/', [QueryDuplicateController::class, 'index'])->name('index');
        Route::post('/clear', [QueryDuplicateController::class, 'clear'])->name('clear');
        Route::delete('/{queryDuplicate}', [QueryDuplicateController::class, 'destroy'])
            ->whereNumber('queryDuplicate')
            ->name('destroy');
    });
