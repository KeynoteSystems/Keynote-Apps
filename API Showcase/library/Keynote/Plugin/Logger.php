<?php

class Keynote_Plugin_Logger extends Zend_Controller_Plugin_Abstract
{
    /**
     * Set up logging
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $logLevel = intval(Zend_Registry::get('config')->general->logLevel);

        $db = Zend_Registry::get('db');

        $columnMapping = array(
            'timestamp' => 'timestamp',
            'lvl'       => 'priorityName',
            'msg'       => 'message');

        $writer = new Zend_Log_Writer_Db($db, 'log', $columnMapping);

        $writer->addFilter(new Zend_Log_Filter_Priority($logLevel));

        Zend_Registry::set('logger', new Zend_Log($writer));
    }
}
