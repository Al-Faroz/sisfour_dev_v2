<?php

namespace App\Support;

use CodeIgniter\HTTP\RequestInterface;
use WeakMap;

final class RequestContext
{
    private static ?WeakMap $store = null;

    public static function set(
        RequestInterface $request,
        string $key,
        mixed $value
    ): void {
        $data = self::all($request);
        $data[$key] = $value;

        self::store()[$request] = $data;
    }

    public static function merge(
        RequestInterface $request,
        array $values
    ): void {
        $data = self::all($request);

        foreach ($values as $key => $value) {
            $data[(string) $key] = $value;
        }

        self::store()[$request] = $data;
    }

    public static function get(
        RequestInterface $request,
        string $key,
        mixed $default = null
    ): mixed {
        $data = self::all($request);

        return array_key_exists($key, $data)
            ? $data[$key]
            : $default;
    }

    public static function all(RequestInterface $request): array
    {
        $store = self::store();

        if (!isset($store[$request])) {
            return [];
        }

        $data = $store[$request];

        return is_array($data) ? $data : [];
    }

    public static function clear(RequestInterface $request): void
    {
        $store = self::store();

        if (isset($store[$request])) {
            unset($store[$request]);
        }
    }

    private static function store(): WeakMap
    {
        if (self::$store === null) {
            self::$store = new WeakMap();
        }

        return self::$store;
    }
}
