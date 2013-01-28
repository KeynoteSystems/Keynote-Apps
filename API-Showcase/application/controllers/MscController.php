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

            case 'msc':
                $ext = 'kms';
                break;
        }

        $lines = explode("\n", $this->_request->getParam('urls'));

        $files_to_zip = array();

        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                list ($scriptName, $actionName, $url) = explode(',', $line);

                $content = file_get_contents('../data/xml/' . $this->_request->getParam('scriptType') . '.xml');
                $content = str_replace('http://www.google.com/', $url, $content);
                $content = str_replace('Google', $actionName, $content);
                if ($this->_request->getParam('scriptType')) {
                    $content = str_replace('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx', $this->create_guid('msc'), $content);
                }
                $f = fopen('scripts/' . $scriptName . '.' . $ext, 'c');

                if($f) {
                    $w[] = array($actionName, $url, $scriptName, $ext);
                    fwrite($f, $content);
                    fclose($f);
                } else {
                    throw new Exception('Failed to update file');
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

    private function create_zip($files = array(), $destination = '', $overwrite = false) {
        /**
         * Check if the zip file already exists and overwrite is false, return false
         */
        if(file_exists($destination) && !$overwrite) {
            return false;
        }

        $valid_files = array();

        if (is_array($files)) {
            foreach($files as $f => $file) {
                /**
                 * Check file(s) exist
                 */
                if(file_exists($file)) {
                    $valid_files[] = $file;
                }
            }
        }

        if (count($valid_files)) {
            $zip = new ZipArchive();
            if ($zip->open($destination, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
                return false;
            }

            foreach($valid_files as $file) {
                $zip->addFile($file,$file);
            }

            $this->view->numFiles =  $zip->numFiles;

            $zip->close();

            /**
             * Check to make sure the file exists
             */
            return file_exists($destination);
        } else {
            return false;
        }
    }

    private function create_guid($namespace = '')
    {
        static $guid = '';
        $uid = uniqid('', true);
        $data = $namespace;
        $data .= $_SERVER['REQUEST_TIME'];
        $data .= $_SERVER['HTTP_USER_AGENT'];
        $data .= $_SERVER['REMOTE_ADDR'];
        $data .= $_SERVER['REMOTE_PORT'];
        $hash = strtoupper(hash('ripemd128', $uid . $guid . md5($data)));
        $guid =
        substr($hash,  0,  8) .
            '-' .
        substr($hash,  8,  4) .
            '-' .
        substr($hash, 12,  4) .
            '-' .
        substr($hash, 16,  4) .
            '-' .
        substr($hash, 20, 12);
        return $guid;
    }
}
