<?php


class Robinhq_Hooks_Model_Exception_BadRequestException extends Exception
{
    protected $message = "The request failed because the data provided was not recognised by the ROBINHQ API";

    protected $code = Robinhq_Hooks_Model_Robin_StatusCode::BAD_REQUEST;
}