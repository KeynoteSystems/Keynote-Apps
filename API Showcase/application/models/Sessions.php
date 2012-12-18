<?php

class Sessions extends Zend_Db_Table
{
    /**
     * Table name
     *
     * @var string
     */
    protected $_name = 'sessions';

    /**
     * Get the current username for current PHP session
     *
     * @return string
     */
    public function getIdentity()
    {
        $sql = $this->select()
            ->from($this->_name, 'username')
            ->where('phpsessid = ?', $_REQUEST['PHPSESSID']);

        $result = $this->fetchRow($sql);

        return $result->username;
    }
}
