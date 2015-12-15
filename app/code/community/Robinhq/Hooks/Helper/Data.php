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

    /**
     * @param $phoneNumber
     * @return mixed|string
     */
    public function formatPhoneNumber($phone) {

        $kentallen = ['0909',
            '0906',
            '0900',
            '0842',
            '0800',
            '0676',
            '06',
            '010',
            '046',
            '0111',
            '0475',
            '0113',
            '0478',
            '0114',
            '0481',
            '0115',
            '0485',
            '0117',
            '0486',
            '0118',
            '0487',
            '013',
            '0488',
            '015',
            '0492',
            '0161',
            '0493',
            '0162',
            '0495',
            '0164',
            '0497',
            '0165',
            '0499',
            '0166',
            '050',
            '0167',
            '0511',
            '0168',
            '0512',
            '0172',
            '0513',
            '0174',
            '0514',
            '0180',
            '0515',
            '0181',
            '0516',
            '0182',
            '0517',
            '0183',
            '0518',
            '0184',
            '0519',
            '0186',
            '0521',
            '0187',
            '0522',
            '020',
            '0523',
            '0222',
            '0524',
            '0223',
            '0525',
            '0224',
            '0527',
            '0226',
            '0528',
            '0227',
            '0529',
            '0228',
            '053',
            '0229',
            '0541',
            '023',
            '0543',
            '024',
            '0544',
            '0251',
            '0545',
            '0252',
            '0546',
            '0255',
            '0547',
            '026',
            '0548',
            '0294',
            '055',
            '0297',
            '0561',
            '0299',
            '0562',
            '030',
            '0566',
            '0313',
            '0570',
            '0314',
            '0571',
            '0315',
            '0572',
            '0316',
            '0573',
            '0317',
            '0575',
            '0318',
            '0577',
            '0320',
            '0578',
            '0321',
            '058',
            '033',
            '0591',
            '0341',
            '0592',
            '0342',
            '0593',
            '0343',
            '0594',
            '0344',
            '0595',
            '0345',
            '0596',
            '0346',
            '0597',
            '0347',
            '0598',
            '0348',
            '0599',
            '035',
            '070',
            '036',
            '071',
            '038',
            '072',
            '040',
            '073',
            '0411',
            '074',
            '0412',
            '075',
            '0413',
            '076',
            '0416',
            '077',
            '0418',
            '078',
            '043',
            '079',
            '045',
        ];
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (substr($phone, 0, 2) == '00' && substr($phone, 2, 2) == '31') {
            $phone = preg_replace('/^([0-9]{4})/', '0', $phone);
        }
        if (substr($phone, 0, 2) == '00') {
            $phone = preg_replace('/^([0-9]{4})([0-9]+)/', '($1) $2', $phone);
        }
        for ($i = 4; $i >= 0; $i--) {
            $ken = substr($phone, 0, $i);
            if (in_array($ken, $kentallen)) {
                break;
            }
        }
        return preg_replace('/([0-9]{' . $i . '})([0-9]+)/', "$1-$2", $phone);
    }


}
	 