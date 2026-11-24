<?php

namespace CI4Smarty;

use CI4Smarty\Config\Services;
use CI4Smarty\Config\Smarty as SmartyConfig;
use CI4Smarty\Debug\Toolbar\Collectors\SmartyViews;
use CodeIgniter\Debug\Toolbar\Collectors\Views;

final class SmartyRenderer
{
    private $savedData;

    private $timelineData = [];

    public function render(string $name, $data, array $options): string
    {
        $collectors = [];
        if (defined('CI_DEBUG') && CI_DEBUG && ($options['debug'] ?? true) === true) {
            $collectors = config('Toolbar')->collectors ?? [];
        }

        $start = in_array(SmartyViews::class, $collectors, true) ? microtime(true) : null;
        $name = $this->normalizeName($name);

        $saveData = array_key_exists('saveData', $options)
            ? (bool) $options['saveData']
            : (bool) (config('View')->saveData ?? true);

        if (is_array($this->savedData) && is_array($data)) {
            $data = array_merge($this->savedData, $data);
        }

        $ttl = $options['cache'] ?? null;
        $cache = null;
        $output = null;
        if (is_int($ttl) && $ttl > 0) {
            $cache = Services::cache();
            $key = $this->cacheKey($options['cache_name'] ?? $name);
            $output = $cache->get($key);
        }

        $cacheHit = is_string($output);
        if (! $cacheHit) {
            $source = strpos($name, ModuleView::PREFIX) === 0 ? ModuleView::locate($name) : $name;
            $smarty = Services::smarty();
            $template = $smarty->createTemplate($source);
            $template->assign('CI', $data);
            $output = $template->fetch();

            if ($cache !== null) {
                $cache->save($key, $output, $ttl);
            }
        }

        if ($saveData) {
            $this->savedData = $data;
        }

        if ($start !== null) {
            $label = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $this->timelineData[] = [
                'name' => 'Smarty view: ' . $label . ($cacheHit ? ' (cache hit)' : ''),
                'component' => 'Smarty Views',
                'start' => $start,
                'duration' => microtime(true) - $start,
            ];
        }

        if (in_array(Views::class, $collectors, true)) {
            $label = str_replace(['--', '<', '>', "\r", "\n"], ['%2D%2D', '%3C', '%3E', '%0D', '%0A'], $name);

            return '<!-- DEBUG-VIEW START ' . $label . ' -->' . PHP_EOL
                . $output . PHP_EOL
                . '<!-- DEBUG-VIEW ENDED ' . $label . ' -->' . PHP_EOL;
        }

        return $output;
    }

    public function clearCache(string $name, ?string $cacheName = null): bool
    {
        return (bool) Services::cache()->delete($this->cacheKey($cacheName ?? $this->normalizeName($name)));
    }

    public function getTimelineData(): array
    {
        return $this->timelineData;
    }

    private function normalizeName(string $name): string
    {
        if (strpos($name, ModuleView::PREFIX) === 0) {
            $name = ModuleView::normalize($name);
        }

        $extension = $_ENV['CI4Smarty.DefaultTemplateExtension']
            ?? SmartyConfig::application()->defaultTemplateExtension
            ?? '.tpl';
        if ($extension !== '' && substr($name, -strlen($extension)) !== $extension) {
            $name .= $extension;
        }

        return $name;
    }

    private function cacheKey(string $name): string
    {
        return 'ci4smarty_' . hash('sha256', $name);
    }
}
