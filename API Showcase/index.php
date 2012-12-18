<?php
define ('APPLICATION_PATH', realpath(dirname(__FILE__)));

/**
 * Set the PHP include path
 */
$paths = array(
APPLICATION_PATH . '/library',
APPLICATION_PATH . '/application/models',
);

set_include_path(implode(PATH_SEPARATOR, $paths) . PATH_SEPARATOR . get_include_path());

/**
 * Autoload required Zend Framework classes
 */
require_once 'Zend/Loader/Autoloader.php';
$loader = Zend_Loader_Autoloader::getInstance();
$loader->registerNamespace('Keynote_');

require APPLICATION_PATH . '/application/bootstrap.php';

Zend_Controller_Front::getInstance()->dispatch();
