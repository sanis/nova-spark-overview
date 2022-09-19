<?php

namespace RhysLees\NovaSparkOverview\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use RhysLees\NovaSparkOverview\Providers\SparkOverviewServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            SparkOverviewServiceProvider::class,
        ];
    }
}
