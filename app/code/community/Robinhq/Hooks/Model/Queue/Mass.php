<?php


class Robinhq_Hooks_Model_Queue_Mass extends Jowens_JobQueue_Model_Job_Abstract {

    /**
     * @var Robinhq_Hooks_Model_Collector
     */
    private $collector;
    /**
     * @var Robinhq_Hooks_Model_Logger
     */
    private $logger;
    /**
     * @var Robinhq_Hooks_Helper_Data
     */
    private $helper;

    /**
     * @param Robinhq_Hooks_Helper_Data $helper
     */
    public function __construct(Robinhq_Hooks_Helper_Data $helper) {

        parent::__construct();
        $this->collector = $helper->getCollector();
        $this->logger = $helper->getLogger();
        $this->helper = $helper;
    }

    public function perform() {

        $this->logger->log('Robin Mass Sender started queueing up customers and orders');
        try {
            $this->collector->customers();
            $this->collector->orders();
            $this->logger->log('Robin Mass Sender finished building the queue. Wait unitll the queue kicks in and handles the jobs');
            $this->helper->noticeAdmin('All customers and orders are send to the queue. Depending on your cron settings, they will soon be sent to ROBIN');
        }
        catch (Exception $e) {
            $this->helper->warnAdmin($e->getMessage());
            $this->logger->log('Mass send failed with message: ' . $e->getMessage());
        }
    }
}