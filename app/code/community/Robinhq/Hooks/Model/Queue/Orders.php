<?php


class Robinhq_Hooks_Model_Queue_Orders extends Robinhq_Hooks_Model_Queue_Abstract
{
    /**
     * @return string
     */
    function getAction()
    {
        return 'orders';
    }

}