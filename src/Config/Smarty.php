<?php

namespace CI4Smarty\Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Extend this class in app/Config/Smarty.php to override package defaults.
 */
class Smarty extends BaseConfig
{
    /** @var string|string[]|null */
    public $templateDir;

    /** @var string|null */
    public $compileDir;

    /** @var string|null */
    public $cacheDir;

    /** @var string|null */
    public $configDir;

    /** @var string|null */
    public $leftDelimiter;

    /** @var string|null */
    public $rightDelimiter;

    /** @var bool|null */
    public $debug;

    /** @var string|null */
    public $defaultTemplateExtension;

    public static function application(): ?self
    {
        if (! is_subclass_of(\Config\Smarty::class, self::class)) {
            return null;
        }

        return config(\Config\Smarty::class);
    }
}
