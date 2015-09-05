<?php


class Robinhq_Hooks_Model_Exception_RequestFailed
{

    public static function factory($statusCode)
    {
        switch ($statusCode) {
            case Robinhq_Hooks_Model_Robin_StatusCode::RATE_LIMIT_EXCEEDED:
                return new Robinhq_Hooks_Model_Exception_RateLimitReachedException();
            case Robinhq_Hooks_Model_Robin_StatusCode::BAD_REQUEST:
                return new Robinhq_Hooks_Model_Exception_BadRequestException();
            case Robinhq_Hooks_Model_Robin_StatusCode::UNAUTHORIZED:
                return new Robinhq_Hooks_Model_Exception_UnauthorizedException();
            case Robinhq_Hooks_Model_Robin_StatusCode::INTERNAL_SERVER_ERROR:
                return new Robinhq_Hooks_Model_Exception_InternalServerErrorException();
            default:
                return new Robinhq_Hooks_Model_Exception_UnknownStatusCodeException($statusCode);
        }
    }
}