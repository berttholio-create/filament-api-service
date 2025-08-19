<?php

use Filament\Facades\Filament;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Rupadana\ApiService\ApiService;
use Rupadana\ApiService\Http\Controllers\AuthController;

Route::prefix('api')
    ->name('api.')
    ->group(function (Router $router) {
        $router->post('/auth/login', [AuthController::class, 'login']);
        $router->post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

        $panels = Filament::getPanels();

        foreach ($panels as $key => $panel) {
            try {
                $hasTenancy = $panel->hasTenancy();
                $tenantRoutePrefix = $panel->getTenantRoutePrefix();
                $tenantSlugAttribute = $panel->getTenantSlugAttribute();
                $apiServicePlugin = $panel->getPlugin('api-service');
                $middlewares = $apiServicePlugin->getMiddlewares();
                
                // Remove panel prefix logic - always use empty string
                $panelRoutePrefix = '';
                $panelNamePrefix = '';

                if (
                    $hasTenancy &&
                    ApiService::isTenancyEnabled() &&
                    ApiService::tenancyAwareness()
                ) {
                    Route::prefix((($tenantRoutePrefix) ? "{$tenantRoutePrefix}/" : '') . '{tenant' . (($tenantSlugAttribute) ? ":{$tenantSlugAttribute}" : '') . '}')
                        ->name($panelNamePrefix)
                        ->middleware($middlewares)
                        ->group(function () use ($panel, $apiServicePlugin) {
                            $apiServicePlugin->route($panel);
                        });
                }

                if (! ApiService::tenancyAwareness()) {
                    Route::prefix($panelRoutePrefix)
                        ->name($panelNamePrefix)
                        ->middleware($middlewares)
                        ->group(function () use ($panel, $apiServicePlugin) {
                            $apiServicePlugin->route($panel);
                        });
                }
            } catch (Exception $e) {
                // Handle exceptions
            }
        }
    });