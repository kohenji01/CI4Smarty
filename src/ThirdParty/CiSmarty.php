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

use Smarty;

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
        
        $this->setTemplateDir  ( $_ENV['CI4Smarty.TemplateDir']    ?? $this->TemplateDir )
            ->setCompileDir    ( $_ENV['CI4Smarty.CompileDir']     ?? $this->CompileDir )
            ->setCacheDir      ( $_ENV['CI4Smarty.CacheDir']       ?? $this->CacheDir )
            ->setConfigDir     ( $_ENV['CI4Smarty.ConfigDir']      ?? $this->ConfigDir )
            ->setDebugging     ( boolval($_ENV['CI4Smarty.Debug']  ?? false) );

        $this->setLeftDelimiter ( $_ENV['CI4Smarty.LeftDelimiter']  ?? $this->LeftDelimiter );
        $this->setRightDelimiter( $_ENV['CI4Smarty.RightDelimiter'] ?? $this->RightDelimiter );
    }
}