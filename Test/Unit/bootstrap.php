<?php

require_once __DIR__ . '/../../vendor/autoload.php';


$autoloader = new \Magento\Framework\TestFramework\Unit\Autoloader\ExtensionGeneratorAutoloader(
    new \Magento\Framework\Code\Generator\Io(
        new \Magento\Framework\Filesystem\Driver\File(),
        './var/generation'
    )
);
spl_autoload_register([$autoloader, 'load']);



\Magento\Framework\Phrase::setRenderer(new \Magento\Framework\Phrase\Renderer\Placeholder());

function __()
{
    $argc = func_get_args();

    $text = array_shift($argc);
    if (!empty($argc) && is_array($argc[0])) {
        $argc = $argc[0];
    }

    return new \Magento\Framework\Phrase($text, $argc);
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
