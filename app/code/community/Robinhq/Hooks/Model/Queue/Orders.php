<?php


class Robinhq_Hooks_Model_Queue_Orders extends Jowens_JobQueue_Model_Job_Abstract {

    /**
     * @var Robinhq_Hooks_Model_Robin_Order[]
     */
    private $orders;

    /**
     * @var false|Robinhq_Hooks_Model_Api
     */
    private $api;

    /**
     * @param Robinhq_Hooks_Model_Api $api
     * @param Robinhq_Hooks_Model_Robin_Order[] $orders
     */
    public function __construct(Robinhq_Hooks_Model_Api $api, $orders) {
        parent::__construct();
        $this->api = $api;
        $this->orders = $orders;
    }

    public function perform() {

        // Add a 1/2 second sleep so we don't hit the robin api limits too quickly
        usleep(500000);
        $this->api->orders($this->orders);
    }
}