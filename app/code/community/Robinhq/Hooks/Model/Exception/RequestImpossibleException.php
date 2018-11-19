<?php


class Robinhq_Hooks_Model_Exception_RequestImpossibleException extends Robinhq_Hooks_Model_Exception_Abstract
{
    protected $message = "Unable to send to the ROBIN API becuase of missing data. Check if you have provided your
    API KEY and SECRET.";
}