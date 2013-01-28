<?php
class MscController extends Zend_Controller_Action
{
    /**
     * Session
     *
     * @var array
     */
    private $_session = array();

    private $_id;

    public function init()
    {
        $this->_session = new Zend_Session_Namespace('DASHBOARD');
        Zend_Session::regenerateId();
        $this->_id = Zend_Session::getId();
        session_write_close();
    }

    public function indexAction()
    {

    }

    public function generateAction()
    {
        switch ($this->_request->getParam('scriptType')) {
            case 'bpdo':
            case 'app:':
                $ext = 'kht';
                break;

            case 'txpdlc':
            case 'txpinactivity':
                $ext = 'krs';
                break;
        }

        $lines = explode("\n", $this->_request->getParam('urls'));

        $files_to_zip = array();

        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                list ($scriptName, $actionName, $url) = explode(',', $line);

                $content = file_get_contents('../data/xml/' . $this->_request->getParam('scriptType') . '.xml');
                $content = str_replace('http://www.google.com', $url, $content);
                $content = str_replace('Google', $actionName, $content);
                $f = fopen('scripts/' . $scriptName . '.' . $ext, 'c');

                if($f) {
                    $w[] = array($actionName, $url, $scriptName, $ext);
                    fwrite($f, $content);
                    fclose($f);
                } else {
                    echo 'Failed to update file';
                }

                array_push($files_to_zip, 'scripts/' . $scriptName . '.' . $ext);
            }

        }

        $this->view->fw = $w;
        $result = $this->create_zip($files_to_zip, 'scripts/' . $this->_id . '.zip');
        $this->view->downloadZip = '/scripts/' . $this->_id . '.zip';

        foreach ($files_to_zip as $files_to_delete) {
            unlink($files_to_delete);
        }

    }

    function create_zip($files = array(), $destination = '', $overwrite = false) {
        //if the zip file already exists and overwrite is false, return false
        if(file_exists($destination) && !$overwrite) {
            return false;
        }
        //vars
        $valid_files = array();
        //if files were passed in...
        if(is_array($files)) {
            //cycle through each file
            foreach($files as $f => $file) {
                //make sure the file exists
                if(file_exists($file)) {
                    $valid_files[] = $file;
                }
            }
        }
        //if we have good files...
        if(count($valid_files)) {
            //create the archive
            $zip = new ZipArchive();
            if($zip->open($destination, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
                return false;
            }
            //add the files
            foreach($valid_files as $file) {
                $zip->addFile($file,$file);
                //unlink($file);
            }
            //debug
            $this->view->numFiles =  $zip->numFiles;
            //close the zip&mdashdone!
            $zip->close();

            //check to make sure the file exists
            return file_exists($destination);
        }
        else
        {
            return false;
        }
    }
}