<?php
/**
 * =============================================================================================
 *  Project: CI4Smarty
 *  File: CiSmarty.php
 *  Date: 2020/05/11 14:32
 *  Author: kohenji
 *  Copyright (c) 2020. SarahSystems lpc.
 *  This software is released under the MIT License, see LICENSE.txt.
 * =============================================================================================
 */
namespace SarahSystems\CI4Smarty\ThirdParty;

use Smarty;

class CiSmarty extends Smarty
{
    public function __construct()
    {
        parent::__construct();
        
        $this->setTemplateDir( $_ENV['CI4Smarty.TemplateDir'] ?? ( APPPATH . 'Views' ) )
            ->setCompileDir  ( $_ENV['CI4Smarty.CompileDir']  ?? ( WRITEPATH . 'smarty' . DIRECTORY_SEPARATOR . 'templates_c' ) )
            ->setCacheDir    ( $_ENV['CI4Smarty.CacheDir']    ?? ( WRITEPATH . 'smarty' . DIRECTORY_SEPARATOR . 'cache' ) )
            ->setConfigDir   ( $_ENV['CI4Smarty.ConfigDir']   ?? ( WRITEPATH . 'smarty' . DIRECTORY_SEPARATOR . 'configs' ) );
    }
}