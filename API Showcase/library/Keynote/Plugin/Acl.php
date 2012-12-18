<?php

class Keynote_Plugin_Acl extends Zend_Controller_Plugin_Abstract
{
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $frontendOptions = array(
            'automatic_cleaning_factor' => 0,
            'cache_id_prefix' => 'Keynote_Acl_',
            'automatic_serialization' => true,
            'lifetime' => null);

        $backendOptions = array(
            'cache_dir' => CACHE);

        $cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);

        if(!$acl = $cache->load('Keynote_Acl')) {

            $user = Zend_Auth::getInstance()->getIdentity();
            $acl = new Keynote_Acl($user);

            //$result = $db->fetchAll('SELECT * FROM huge_table');

            $cache->save($acl, 'Keynote_Acl');

        } //else {

            // cache hit! shout so that we know
           // echo "This one is from cache!\n\n";

        //}

//        print_r($acl);

        Zend_Registry::set('acl', $acl);

    }
}
