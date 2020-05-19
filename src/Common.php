<?php
/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the frameworks
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @link: https://codeigniter4.github.io/CodeIgniter4/
 */

namespace CI4Smarty;

use CI4Smarty\Config\Services;
use Exception;


/**
 * @param string $name
 * @param mixed $data
 * @param array $options Unused - reserved for third-party extensions.
 *
 * @return string
 * @throws Exception
 */
function view(string $name, $data = [], array $options = []): string
{
    try {
        $ext = $_ENV['CI4Smarty.DefaultTemplateExtension'] ?? '.tpl';
        $smarty = Services::smarty();
        unset($options); // 互換性のため維持。不要なのでunset
        if (substr($name, 0, -strlen($ext)) != $ext) {
            $name .= $ext;
        }
        $smarty->assign('CI', $data);
    } catch (Exception $e) {
        throw $e;
    }
    return $smarty->fetch($name);
}
