<?php


/**
 * Class Robinhq_Hooks_Helper_Data
 */
class Robinhq_Hooks_Helper_Data extends Mage_Core_Helper_Abstract {

    /**
     * @var Robinhq_Hooks_Model_Queue
     */
    private $queue;

    /**
     * @var Robinhq_Hooks_Model_Logger
     */
    private $logger;

    /**
     * @var Robinhq_Hooks_Model_Robin_Converter
     */
    private $converter;

    private $bulkLimit;

    private $selectLimit;

    private $api;

    /**
     * @var Robinhq_Hooks_Model_Collector
     */
    private $collector;

    /**
     * Gets and sets the dependency's
     */
    public function __construct() {

        $config = Mage::getStoreConfig('settings/general');
        $this->bulkLimit = (int)$config['bulk_limit'];
        $this->selectLimit = (int)$config['select_limit'];
        $this->logger = new Robinhq_Hooks_Model_Logger();
        $this->api = new Robinhq_Hooks_Model_Api($this->logger);
        $this->queue = new Robinhq_Hooks_Model_Queue($this->logger, $this->api, $this->bulkLimit);
        $this->converter = new Robinhq_Hooks_Model_Robin_Converter();
        $this->collector = new Robinhq_Hooks_Model_Collector($this->queue, $this->converter, $this->selectLimit);
    }

    /**
     * @param $message
     */
    public function log($message) {

        $this->logger->log($message);
    }

    /**
     * @return Robinhq_Hooks_Model_Queue
     */
    public function getQueue() {

        return $this->queue;
    }

    /**
     * @return Robinhq_Hooks_Model_Api
     */
    public function getApi() {

        return $this->api;
    }

    public function getLogger() {

        return $this->logger;
    }

    public static function warnAdmin($warning) {

        Mage::getSingleton('adminhtml/session')->addWarning("Robin: " . $warning);
    }

    public static function noticeAdmin($notice) {

        Mage::getSingleton('adminhtml/session')->addSuccess("Robin: " . $notice);
    }

    public static function formatPrice($price) {

        return Mage::helper('core')->currency($price, true, false);
    }

    /**
     * @return Robinhq_Hooks_Model_Robin_Converter
     */
    public function getConverter() {

        return $this->converter;
    }

    /**
     * @return Robinhq_Hooks_Model_Collector
     */
    public function getCollector() {

        return $this->collector;
    }


}
	 