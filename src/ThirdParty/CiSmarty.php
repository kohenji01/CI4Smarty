<?php
/**
 * =============================================================================================
 *  Project: CI4Smarty
 *  File: CiSmarty.php
 *  Date: 2020/05/15 17:54
 *  Author: Shoji Ogura <kohenji@sarahsytems.com>
 *  Copyright (c) 2020. SarahSystems lpc.
 *  This software is released under the MIT License, see LICENSE.txt.
 * =============================================================================================
 */

namespace CI4Smarty\ThirdParty;

use CI4Smarty\Config\Smarty as SmartyConfig;
use Smarty\Smarty;

class CiSmarty extends Smarty
{
    protected $TemplateDir    = APPPATH . 'Views';
    protected $CompileDir     = WRITEPATH . 'smarty' . DIRECTORY_SEPARATOR . 'templates_c';
    protected $LeftDelimiter  = "{";
    protected $RightDelimiter = "}";
    protected $CacheDir       = WRITEPATH . 'smarty' . DIRECTORY_SEPARATOR . 'cache';
    protected $ConfigDir      = WRITEPATH . 'smarty' . DIRECTORY_SEPARATOR . 'configs';
    
    public function __construct()
    {
        parent::__construct();

        $config = SmartyConfig::application();

        $this->setTemplateDir($_ENV['CI4Smarty.TemplateDir'] ?? $config->templateDir ?? $this->TemplateDir)
            ->setCompileDir($_ENV['CI4Smarty.CompileDir'] ?? $config->compileDir ?? $this->CompileDir)
            ->setCacheDir($_ENV['CI4Smarty.CacheDir'] ?? $config->cacheDir ?? $this->CacheDir)
            ->setConfigDir($_ENV['CI4Smarty.ConfigDir'] ?? $config->configDir ?? $this->ConfigDir);

        $this->setDebugging(filter_var($_ENV['CI4Smarty.Debug'] ?? $config->debug ?? false, FILTER_VALIDATE_BOOLEAN));
        $this->setLeftDelimiter($_ENV['CI4Smarty.LeftDelimiter'] ?? $config->leftDelimiter ?? $this->LeftDelimiter);
        $this->setRightDelimiter($_ENV['CI4Smarty.RightDelimiter'] ?? $config->rightDelimiter ?? $this->RightDelimiter);
    }
}
