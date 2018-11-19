<?php


/**
 * Class Robinhq_Hooks_Helper_Data
 */
class Robinhq_Hooks_Helper_Data extends Mage_Core_Helper_Abstract
{

    /** Config base */
    CONST CONFIG_BASE = 'settings/general/';

    /** @var Robinhq_Hooks_Model_Queue */
    protected $queue;
    /** @var Robinhq_Hooks_Model_Robin_Converter */
    protected $converter;
    /** @var Robinhq_Hooks_Model_Collector */
    protected $collector;

    /** @var Robinhq_Hooks_Model_Api */
    protected $api;

    /** @var Robinhq_Hooks_Model_Logger */
    protected $logger;

    /**
     * Gets and sets the dependency's
     */
    public function __construct()
    {
        $this->logger = $logger = Mage::getModel('robinhq_hooks/logger');
        $this->api = Mage::getModel('robinhq_hooks/api', $this);
        $this->queue = Mage::getModel('robinhq_hooks/queue', $this);
        $this->converter = Mage::getModel('robinhq_hooks/robin_converter');
        $this->collector = Mage::getModel('robinhq_hooks/collector', $this);
    }

    /**
     * Tell if module is enabled in configuration
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->getConfigFlag('enabled');
    }

    /**
     * Get config base
     *
     * @param string $name
     * @return mixed
     */
    public function getConfig($name)
    {
        return Mage::getStoreConfig(self::CONFIG_BASE . $name);
    }

    /**
     * Get config flag
     *
     * @param string $name
     * @return bool
     */
    public function getConfigFlag($name)
    {
        return !!$this->getConfig($name);
    }

    /**
     * Log message
     *
     * @param string $message
     */
    public function log($message)
    {
        $this->getLogger()
                ->log($message);
    }

    /**
     * Get queue
     *
     * @return Robinhq_Hooks_Model_Queue
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Get API
     *
     * @return Robinhq_Hooks_Model_Api
     */
    public function getApi()
    {
        return $this->api;
    }

    /**
     * Get logger
     *
     * @return Robinhq_Hooks_Model_Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Add admin message
     */
    public static function warnAdmin($warning)
    {
        Mage::getSingleton('adminhtml/session')
                ->addWarning("Robin: " . $warning);
    }

    /**
     * Add admin message
     */
    public static function noticeAdmin($notice)
    {
        Mage::getSingleton('adminhtml/session')
                ->addSuccess("Robin: " . $notice);
    }

    /**
     * Format price
     *
     * @param float $price
     */
    public static function formatPrice($price)
    {
        return Mage::helper('core')
                ->currency($price, true, false);
    }

    /**
     * Get converter
     *
     * @return Robinhq_Hooks_Model_Robin_Converter
     */
    public function getConverter()
    {
        return $this->converter;
    }

    /**
     * Get collector
     *
     * @return Robinhq_Hooks_Model_Collector
     */
    public function getCollector()
    {
        return $this->collector;
    }

    /**
     * Format phone number
     *
     * @param string $phoneNumber
     * @param string $countryCode
     * @return string
     */
    public function formatPhoneNumber($phoneNumber, $countryCode)
    {
        $phoneNumberClean = preg_replace("/[^\\d]+/s", '', $phoneNumber);
        $length = +strlen($phoneNumberClean);
        if (10 === $length) {
            return $phoneNumber;
        }

        if (11 === $length && 'NL' === $countryCode && strpos($phoneNumberClean, '31') === 0) {
            return '0' . substr($phoneNumberClean, 2);
        }

        return $phoneNumberClean;
    }

    /**
     * Get reward points for a customer
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return int
     */
    public function getRewardPoints($customer)
    {
        if (!$customer->getId()) {
            return 0;
        }

        if (!Mage::getConfig()->getModuleConfig('Rewardpoints')->is('active', 'true')) {
            return 0;
        }

        $rewardFlatModel = Mage::getModel('rewardpoints/flatstats');
        $rewardModel = Mage::getModel('rewardpoints/stats');

        $points = 0;
        $storeIds = array_keys(Mage::app()->getStores());
        foreach ($storeIds as $storeId) {
            if ($rewardFlatModel && Mage::getStoreConfigFlag('rewardpoints/default/flatstats', $storeId)) {
                $points += +$rewardFlatModel->collectPointsCurrent($customer->getId(), $storeId);
            } elseif ($rewardModel) {
                $points += +$rewardModel->getPointsCurrent($customer->getId(), $storeId);
            }
        }

        return $points;
    }

    /**
     * @param string $route
     * @param array $params
     *
     * @return string
     */
    public function getUrl($route = '', $params = array())
    {
        return Mage::getModel('adminhtml/url')->setStore(Mage_Core_Model_App::ADMIN_STORE_ID)->getUrl($route, $params);
    }

}
	 