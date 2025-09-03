<?php

namespace App\Providers;

use App\Enums\AbandonedObjectTypeEnum;
use App\Services\RequestStrategies\AbandonedObjectRequestStrategy;
use App\Services\RequestStrategies\HouseRequestStrategy;
use App\Services\RequestStrategies\LandPlotRequestStrategy;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    private const STRATEGY_MAP = [
        AbandonedObjectTypeEnum::HOUSE->value => HouseRequestStrategy::class,
        AbandonedObjectTypeEnum::LAND_PLOT->value => LandPlotRequestStrategy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AbandonedObjectRequestStrategy::class, function ($app) {
            $request = $app->make(Request::class);
            $type = AbandonedObjectTypeEnum::tryFrom((int) $request->input('type')) ?? AbandonedObjectTypeEnum::HOUSE;

            $strategyClass = self::STRATEGY_MAP[$type->value] ?? HouseRequestStrategy::class;

            return $app->make($strategyClass);
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
