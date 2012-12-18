<?php

class Keynote_Plugin_Language extends Zend_Controller_Plugin_Abstract
{
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        try {
            $locale = new Zend_Locale('auto');
        } catch (Zend_Locale_Exception $e) {
            $locale = new Zend_Locale('en');
        }

        Zend_Registry::set('locale', $locale->getLanguage());

        setcookie('lang', $locale->getLanguage(), null, '/');
        /*
        $frontendOptions = array(
            'automatic_cleaning_factor' => 0,
            'cache_id_prefix'           => 'KEYN_',
            'lifetime'                  => null);

        $backendOptions = array(
            'cache_dir' => CACHE);

        $cache = Zend_Cache::factory('Page', 'File', $frontendOptions, $backendOptions);

        Zend_Translate::setCache($cache);


        $options = array(
            'scan'           => Zend_Translate::LOCALE_DIRECTORY,
            'disableNotices' => true,
            'ignore'         => 'js');

        $translate = new Zend_Translate('csv', 'languages', 'auto', $options);

        Zend_Registry::set('Zend_Translate', $translate);
        */
    }
}
