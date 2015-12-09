<?php


class Robinhq_Hooks_Model_Exception_RateLimitReachedException extends Exception
{
    protected $message = "The rate limit for the ROBIN API was reached. You can send 3 request per second, 180 per
    minute, 1000 per hour and 100000 per day.";

    protected $code = Robinhq_Hooks_Model_Robin_StatusCode::RATE_LIMIT_EXCEEDED;
}