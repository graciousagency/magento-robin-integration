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

    public function formatPhoneNumber($phoneNumber, $countryCode) {
        Mage::log(__METHOD__, null, 'pepijn.log');
        $phoneNumberClean = preg_replace("/[^\d]/", "", $phoneNumber);
        Mage::log('$countryCode = ' . $countryCode, null, 'pepijn.log');
        Mage::log('$phoneNumber = ' . $phoneNumber, null, 'pepijn.log');
        Mage::log('$phoneNumberClean = ' . $phoneNumberClean, null, 'pepijn.log');
        $length = strlen($phoneNumber);
        Mage::log('$length = ' . $length, null, 'pepijn.log');
        if($length == 10) {
            $phoneNumberFormatted = $phoneNumber;
        }
        if($length == 11) {
            Mage::log('$VAR = ' . substr($phoneNumberClean,0,2), null, 'pepijn.log');
            if($countryCode=='NL')  {
                if(substr($phoneNumberClean, 0, 2)=='31') {
                    $phoneNumberFormatted = substr_replace($phoneNumberClean,'0',0,2);
                }
            }else{
                $phoneNumberFormatted = $phoneNumber;
            }

        }
        Mage::log('$phoneNumberFormatted = ' . $phoneNumberFormatted, null, 'pepijn.log');
        return $phoneNumberFormatted;
    }

    /**
     * Gets the amount of rewardpoints a customer has saved up
     *
     * @return int
     */
    public function getRewardPoints() {
        $points = 0;
        $allStores = Mage::app()->getStores();
        if ($this->customer->getId()) {
            foreach ($allStores as $_eachStoreId => $val) {
                $_storeId = Mage::app()->getStore($_eachStoreId)->getId();
                if (Mage::getStoreConfig('rewardpoints/default/flatstats', $_storeId)) {
                    $reward_flat_model = Mage::getModel('rewardpoints/flatstats');
                    $points += $reward_flat_model->collectPointsCurrent($this->customer->getId(), $_storeId) + 0;
                } else {
                    $reward_model = Mage::getModel('rewardpoints/stats');
                    $points += $reward_model->getPointsCurrent($this->customer->getId(), $_storeId) + 0;
                }
            }
        }
        return $points;
    }



}
	 