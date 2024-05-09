<?php
/**
 * Billing module for laravel projects
 *
 * @link      https://github.com/arhitov/laravel-billing
 * @package   arhitov/laravel-billing
 * @license   MIT
 * @copyright Copyright (c) 2024, Alexander Arhitov, clgsru@gmail.com
 */

namespace Arhitov\LaravelBilling\Tests\Fixtures;

use RuntimeException;

trait FixtureTrait
{
    /**
     * @param string $name
     * @return string|array
     */
    protected function fixture(string $name): string|array
    {
        if (str_contains($name, '..')) {
            throw new RuntimeException("Bad name fixture");
        }

        $fixture = __DIR__ . '/fixture/' . $name;
        if (file_exists($fixture . '.json')) {
            return file_get_contents($fixture . '.json');
        }
        if (file_exists($fixture . '.php')) {
            return require $fixture . '.php';
        }

        throw new RuntimeException("Not found fixture \"{$name}\"");
    }

    /**
     * @param string $name
     * @return array
     */
    protected function fixtureAsArray(string $name): array
    {
        $data = $this->fixture($name);
        return is_string($data)
            ? json_decode($data, true, JSON_UNESCAPED_UNICODE)
            : $data;
    }
}
