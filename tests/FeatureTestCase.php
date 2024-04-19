<?php

namespace Arhitov\LaravelBilling\Tests;

use Arhitov\LaravelBilling\Contracts\BillableInterface;
use Arhitov\LaravelBilling\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * @param array $options
     * @return Model|BillableInterface
     */
    protected function createOwner(array $options = []): Model|BillableInterface
    {
        $fake = fake();
        return User::create(array_merge([
            'email' => $fake->unique()->safeEmail(),
            'name' => $fake->name(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        ], $options));
    }
}
