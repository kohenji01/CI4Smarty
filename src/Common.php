<?php

namespace CI4Smarty;

use CI4Smarty\Config\Services;

/**
 * Render a Smarty template with the supplied data available as $CI.
 *
 * @param mixed $data
 * @param array $options Supports saveData, cache, cache_name and debug.
 */
function view(string $name, $data = [], array $options = []): string
{
    return Services::smartyRenderer()->render($name, $data, $options);
}

/**
 * Delete a single CI4Smarty HTML cache entry.
 */
function clear_view_cache(string $name, ?string $cacheName = null): bool
{
    return Services::smartyRenderer()->clearCache($name, $cacheName);
}
