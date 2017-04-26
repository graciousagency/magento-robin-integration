<?php


class Robinhq_Hooks_Model_Exception_InternalServerErrorException extends Robinhq_Hooks_Model_Exception_Abstract
{
    protected $message = 'The request failed because of an internal server error on the ROBINHQ server';

    protected $code = Robinhq_Hooks_Model_Robin_StatusCode::INTERNAL_SERVER_ERROR;
}