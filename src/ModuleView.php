<?php

namespace CI4Smarty;

use CI4Smarty\Config\Services;
use Smarty\Exception;

final class ModuleView
{
    public const PREFIX = 'module:';

    public static function normalize(string $name): string
    {
        $relative = str_replace('/', '\\', substr($name, strlen(self::PREFIX)));
        $segments = explode('\\', $relative);
        if (count($segments) < 2 || preg_match('/[\x00-\x1F:]/', $relative)
            || array_intersect($segments, ['', '.', '..'])
        ) {
            throw new Exception('Invalid CI4Smarty module template: ' . $name);
        }

        return self::PREFIX . $relative;
    }

    public static function locate(string $name): string
    {
        $relative = substr($name, strlen(self::PREFIX));
        $prefix = '';
        $paths = [];
        foreach (Services::autoloader()->getNamespace() as $namespace => $directories) {
            $namespace = trim($namespace, '\\');
            if (strlen($namespace) > strlen($prefix) && strpos($relative, $namespace . '\\') === 0) {
                $prefix = $namespace;
                $paths = $directories;
            }
        }

        if ($prefix === '') {
            throw new Exception('No CI4 namespace registered for Smarty module template: ' . $name);
        }

        $path = self::find(APPPATH . 'Views/overrides', str_replace('\\', '/', $relative));
        if ($path !== null) {
            return $path;
        }

        $file = str_replace('\\', '/', substr($relative, strlen($prefix) + 1));
        foreach ($paths as $directory) {
            $path = self::find(rtrim($directory, '\\/') . '/Views', $file);
            if ($path !== null) {
                return $path;
            }
        }

        throw new Exception('Smarty module template not found: ' . $name);
    }

    private static function find(string $directory, string $file): ?string
    {
        $root = realpath($directory);
        if ($root === false) {
            return null;
        }

        $root = rtrim($root, '\\/') . DIRECTORY_SEPARATOR;
        $path = realpath($root . $file);
        if ($path === false) {
            return null;
        }

        $comparison = DIRECTORY_SEPARATOR === '\\' ? 'strncasecmp' : 'strncmp';
        if ($comparison($path, $root, strlen($root)) !== 0) {
            throw new Exception('Smarty module template is outside its view directory: ' . $file);
        }

        return is_file($path) ? $path : null;
    }
}
