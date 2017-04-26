<?php

class Robinhq_Hooks_Model_Queue_Mass extends Jowens_JobQueue_Model_Job_Abstract
{

    /**
     * @var Robinhq_Hooks_Model_Collector
     */
    protected $collector;

    /**
     * @var Robinhq_Hooks_Helper_Data
     */
    protected $helper;

    /**
     * @param Robinhq_Hooks_Helper_Data $helper
     */
    public function __construct(Robinhq_Hooks_Helper_Data $helper)
    {
        parent::__construct();

        $this->collector = $helper->getCollector();
        $this->helper = $helper;
    }

    public function perform()
    {
        $helper = $this->helper;
        $collector = $helper->getCollector();

        $helper->log('Robin Mass Sender started queueing up customers and orders');

        try {
            $collector->customers();
            $collector->orders();

            $helper->log('Robin Mass Sender finished building the queue. Wait until the queue kicks in and handles the jobs');
            $helper->noticeAdmin('All customers and orders are send to the queue. Depending on your cron settings, they will soon be sent to ROBIN');
        }
        catch (Exception $e) {
            $helper->warnAdmin($e->getMessage());
            $helper->log('Mass send failed with message: ' . $e->getMessage());
        }

    }

}