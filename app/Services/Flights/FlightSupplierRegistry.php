<?php

namespace App\Services\Flights;

use App\Contracts\FlightSupplier;
use InvalidArgumentException;

/**
 * Resolves flight suppliers by key from config('flights.suppliers').
 *
 * Only knows which suppliers exist. Whether one is switched on, and in what
 * order they are tried, is a separate concern that arrives with the admin
 * controls.
 */
class FlightSupplierRegistry
{
    /** @return list<string> */
    public function keys(): array
    {
        return array_keys((array) config('flights.suppliers', []));
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, (array) config('flights.suppliers', []));
    }

    public function get(string $key): FlightSupplier
    {
        $class = config("flights.suppliers.{$key}");

        if (! is_string($class) || $class === '') {
            throw new InvalidArgumentException("Unknown flight supplier [{$key}].");
        }

        $supplier = app($class);

        if (! $supplier instanceof FlightSupplier || $supplier->key() !== $key) {
            throw new InvalidArgumentException("Flight supplier [{$key}] is misconfigured.");
        }

        return $supplier;
    }

    /** @return array<string, FlightSupplier> */
    public function all(): array
    {
        $suppliers = [];

        foreach ($this->keys() as $key) {
            $suppliers[$key] = $this->get($key);
        }

        return $suppliers;
    }
}
