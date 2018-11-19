<?php


class Robinhq_Hooks_Model_Exception_RequestFailed
{

    /**
     * Get throwable exception
     *
     * @param string $statusCode
     * @return Robinhq_Hooks_Model_Exception_Abstract
     */
    public static function factory($statusCode)
    {
        /** @var Robinhq_Hooks_Model_Robin_StatusCode $statusCode */
        $statusCodes = Mage::getModel('robinhq_hooks/robin_statusCode');

        switch ($statusCode) {
            case $statusCodes::RATE_LIMIT_EXCEEDED:
                $errorModel = 'rateLimitReachedException';
                break;

            case $statusCodes::BAD_REQUEST:
                $errorModel = 'badRequestException';
                break;

            case $statusCodes::UNAUTHORIZED:
                $errorModel = 'unauthorizedException';
                break;

            case $statusCodes::INTERNAL_SERVER_ERROR:
                $errorModel = 'internalServerErrorException';
                break;

            default:
                $errorModel = 'unknownStatusCodeException';

        }

        return Mage::getModel('robinhq_hooks/exception_' . $errorModel, '');
    }
}
