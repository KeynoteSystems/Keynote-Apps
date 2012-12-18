<?php
define ('CACHE', APPLICATION_PATH . '/application/temp/cache');

ini_set('session.save_path', APPLICATION_PATH . '/application/temp/sessions');

date_default_timezone_set('Europe/London');

$devConfig = APPLICATION_PATH . '/application/config/dashboard-dev.ini';

$config = new Zend_Config_Ini(APPLICATION_PATH . '/application/config/dashboard.ini', null, true);

if (is_file($devConfig)) {
    $localConfig = new Zend_Config_Ini($devConfig, null, true);
    $config->merge($localConfig);
}

$config->setReadOnly();

/**
 * Define PHP ini settings
 * These can be removed for a release version!
 */
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', true);
//ini_set('log_errors', (bool) DEVELOPER_MODE);

/**
 * Set up database
 */
//$db = Zend_Db::factory('PDO_SQLITE', array(
//    'dbname' => APPLICATION_PATH . '/database/magik.db3'));
//Zend_Db_Table::setDefaultAdapter($db);

/**
 * Set up layout
 */
Zend_Layout::startMvc();

$view = Zend_Layout::getMvcInstance()->getView();

//$releaseMode = $config->general->releaseMode;

//$mode = isset($releaseMode) ? $releaseMode : true;

//$view->theme = $config->general->defaultTheme;

/**
 * Set up controller
 */
$controller = Zend_Controller_Front::getInstance();

$controller->setControllerDirectory(APPLICATION_PATH . '/application/controllers')
//->registerPlugin(new Keynote_Plugin_Logger())
->registerPlugin(new Keynote_Plugin_Language());

/**
 * Add routes
 */
/*
$route = $controller->getRouter();// Returns a rewrite router by default

$route->addRoute('load', new Zend_Controller_Router_Route('load/:_loadApplication/*', array(
    '_loadApplication' => '',
    'mode'             => 'load',
    'fromUrl'          => true,
    'controller'       => 'save',
    'action'           => 'application')));

$route->addRoute('log', new Zend_Controller_Router_Route('log/:level', array(
    'level'      => '',
    'controller' => 'debug',
    'action'     => 'log')));

$route->addRoute('acl', new Zend_Controller_Router_Route('acl', array(
        'level'      => '',
        'controller' => 'debug',
        'action'     => 'acl')));

$route->addRoute('lang', new Zend_Controller_Router_Route('lang', array(
        'level'      => '',
        'controller' => 'debug',
        'action'     => 'lang')));

//$route->addRoute('acl', new Zend_Controller_Router_Route('inspector', array(
//        'level'      => '',
//        'controller' => 'debug',
//        'action'     => 'inspector')));
/*
 * REGISTRY - setup the application registry
 */
$registry = Zend_Registry::getInstance();
$registry->config = $config;
//$registry->db     = $db;

/*
 * CLEANUP - remove items from global scope
 */
//unset($controller, $view, $config, $db, $route);
unset($controller, $view, $config);
