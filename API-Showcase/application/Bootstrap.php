<?php
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    public function _initConfig()
    {
        $config = $this->getOption('keynote');
        Zend_Registry::set('config', $config);
    }

    public function _initLogger()
    {
        if ('development' == APPLICATION_ENV) {
            $this->bootstrap("log");
            $logger = $this->getResource("log");
            Zend_Registry::set("logger", $logger);
        }
    }
}
