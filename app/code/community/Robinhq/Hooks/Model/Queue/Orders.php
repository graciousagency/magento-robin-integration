<?php


class Robinhq_Hooks_Model_Queue_Orders extends Jowens_JobQueue_Model_Job_Abstract
{
    /**
     * @var Robinhq_Hooks_Model_Robin_Order[]
     */
    private $orders;

    /**
     * @var false|Robinhq_Hooks_Model_Api
     */
    private $api;

    /**
     * @param Robinhq_Hooks_Model_Robin_Order[] $orders
     * @param Robinhq_Hooks_Model_Api $api
     */
    public function __construct($orders, Robinhq_Hooks_Model_Api $api)
    {
        parent::__construct();
        $this->api = $api;
        $this->orders = $orders;
    }

    public function perform()
    {
        $this->api->orders($this->orders);
    }
}