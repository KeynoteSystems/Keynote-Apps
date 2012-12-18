<?php

class ErrorController extends Zend_Controller_Action
{
    /**
     * errorAction
     *
     */
    public function errorAction()
    {
        $this->_helper->viewRenderer->setViewSuffix('phtml');

        $errors = $this->_getParam('error_handler');

        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                // 404 error -- controller or action not found
                $this->getResponse()->setHttpResponseCode(404);
                $this->view->message = 'Page not found';
                break;
            default:
                // application error
                $this->getResponse()->setHttpResponseCode(500);
                $this->view->message = 'Application error';
                break;
        }

        $this->view->appTitle = 'DASHBOARD';

        $this->view->developerMode = DEVELOPER_MODE;

        // pass the actual exception object to the view
        $this->view->exception = $errors->exception;

        // pass the request to the view
        $this->view->request   = $errors->request;
    }
}
