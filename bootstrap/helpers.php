<?php

if (!function_exists('database_path')) {
    function database_path($path = '') {
        return __DIR__ . '/../database' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('config_path')) {
    function config_path($path = '') {
        return __DIR__ . '/../config' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('storage_path')) {
    function storage_path($path = '') {
        return __DIR__ . '/../storage' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('public_path')) {
    function public_path($path = '') {
        return __DIR__ . '/../public' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('resource_path')) {
    function resource_path($path = '') {
        return __DIR__ . '/../resources' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('base_path')) {
    function base_path($path = '') {
        return __DIR__ . '/..' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('app_path')) {
    function app_path($path = '') {
        return __DIR__ . '/../app' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('branch_label')) {
    function branch_label($branch)
    {
        return match($branch) {
            'las_pinas' => 'Las Piñas',
            'cebu' => 'Cebu',
            'davao' => 'Davao',
            default => ucfirst(str_replace(['_', '-'], ' ', $branch ?? ''))
        };
    }
}
