<?php

// PHP Unit backward compatibility
if (class_exists('\PHPUnit\Framework\TestCase') &&
    !class_exists('\PHPUnit_Framework_TestCase')) {
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase');
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
