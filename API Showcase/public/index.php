<?php
defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

set_include_path(implode(PATH_SEPARATOR, array(realpath(APPLICATION_PATH . '/../library'), get_include_path())));

require_once 'Zend/Application.php';

$application = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/config/application.ini');

$application->bootstrap()->run();