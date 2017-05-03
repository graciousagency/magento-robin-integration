<?php


class Robinhq_Hooks_Model_Exception_UnauthorizedException extends Robinhq_Hooks_Model_Exception_Abstract
{
    protected $message = "Your API credentials are not accepted by the ROBINHQ API, please check if you have provided
     them in system -> configuration -> ROBINHQ -> API Settings";

    protected $code = Robinhq_Hooks_Model_Robin_StatusCode::UNAUTHORIZED;
}