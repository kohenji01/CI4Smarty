<?php

namespace CI4Smarty;

use CI4Smarty\Config\Services;
use CodeIgniter\Debug\Toolbar\Collectors\Views;

final class SmartyRenderer
{
    private $savedData;

    public function render(string $name, $data, array $options): string
    {
        $extension = $_ENV['CI4Smarty.DefaultTemplateExtension'] ?? '.tpl';
        if ($extension !== '' && substr($name, -strlen($extension)) !== $extension) {
            $name .= $extension;
        }

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
            $key = 'ci4smarty_' . hash('sha256', $options['cache_name'] ?? $name);
            $output = $cache->get($key);
        }

        if (! is_string($output)) {
            $smarty = Services::smarty();
            $template = $smarty->createTemplate($name);
            $template->assign('CI', $data);
            $output = $template->fetch();

            if ($cache !== null) {
                $cache->save($key, $output, $ttl);
            }
        }

        if ($saveData) {
            $this->savedData = $data;
        }

        if (defined('CI_DEBUG') && CI_DEBUG && ($options['debug'] ?? true) === true
            && in_array(Views::class, config('Toolbar')->collectors ?? [], true)
        ) {
            $label = str_replace(['--', '<', '>', "\r", "\n"], ['%2D%2D', '%3C', '%3E', '%0D', '%0A'], $name);

            return '<!-- DEBUG-VIEW START ' . $label . ' -->' . PHP_EOL
                . $output . PHP_EOL
                . '<!-- DEBUG-VIEW ENDED ' . $label . ' -->' . PHP_EOL;
        }

        return $output;
    }
}
