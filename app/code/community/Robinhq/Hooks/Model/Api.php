<?php

/**
 * A small wrapper to communicate with the Robin API.
 * Class Robinhq_Hooks_Model_Api
 */
class Robinhq_Hooks_Model_Api {

    /**
     * @var Robinhq_Hooks_Model_Logger
     */
    private $logger;

    /**
     * @var Robinhq_Hooks_Model_RobinCustomer
     */
    private $robinCustomer;

    /**
     * @var Robinhq_Hooks_Model_RobinOrder
     */
    private $robinOrder;

    /**
     * Gets and sets the dependency's
     */
    public function __construct(){
        $this->logger = Mage::getModel('hooks/logger');
        $this->robinCustomer = Mage::getModel('hooks/robinCustomer');
        $this->robinOrder = Mage::getModel('hooks/robinOrder');
    }

    /**
     * Makes from an array of Magento customers an array of Robinhq_Hooks_Model_RobinCustomer's
     * and sends it to the Robin API.
     *
     * @param $customers
     * @return mixed
     */
    function customers($customers){
        $robinCustomers = array();
        foreach ($customers as $customer) {
            $robinCustomers[] = $this->toRobinCustomer($customer);
        }
        $this->logger->log("Sending customer to ROBIN");
        return $this->post('customers', array('customers' => $robinCustomers));
    }

    /**
     * Makes from an array of Magento orders an array of Robinhq_Hooks_Model_RobinOrder's
     * and sends it to the Robin API.
     *
     * @param $orders
     * @return mixed
     */
    function orders($orders){
        $robinOrders = array();
        foreach($orders as $order){
            $robinOrders[] = $this->toRobinOrder($order);
        }
        $this->logger->log("Sending order to ROBIN");
        return $this->post('orders', array('orders' => $robinOrders));
    }


    /**
     * Converts a Mage_Customer_Model_Customer into a simple array
     * with key/value pairs that are required by the Robin API.
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return array
     */
    function toRobinCustomer(Mage_Customer_Model_Customer $customer){
        return $this->robinCustomer->factory($customer);
    }


    /**
     * Converst a Mage_Sales_Model_Order into a array with required key/value pairs and a
     * example details_view.
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    function toRobinOrder(Mage_Sales_Model_Order $order){
        return $this->robinOrder->factory($order);
    }

    /**
     * Sets up the CURl request by loading the settings file and preparing the headers with
     * basic auth.
     * Throws exception when settings file is not found
     * @param $request
     * @throws Exception
     * @return bool|resource
     */
    function setUpCurl($request){
        $config = Mage::getStoreConfig('settings/general');
        $this->logger->log(json_encode($config));
        if(!empty($config['api_key']) && !empty($config['api_secret'])){
            $url = $config['baseUrl'] . $request;
            $this->logger->debug($url);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_USERPWD, $config['apikey'] . ":" . $config['secret']);
            return $ch;
        }
        throw new Exception('Missing API configuration, go to Admin Panel -> System -> Configuration -> ROBINHQ -> Settings and fill in your API credentials');
    }

    /**
     * Posts the values as a json string to the Robin API endpoint given in $request.
     *
     * @param $request
     * @param $values
     * @throws Exception
     * @return mixed
     */
    function post($request, $values){
        try {
            $errorCodes = array(500, 400, 401);
            $values = json_encode($values);
            $ch = $this->setUpCurl($request);
        }
        catch(Exception $e){
            $this->abort($e->getMessage());
            return false;
        }
        $valuesLength = strlen($values);
        $this->logger->log("Posting with: " . $values);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . $valuesLength
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $values);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (curl_exec($ch) === false) {
            curl_close($ch);
            throw new Exception("Exception: Request to Robin failed");
        }
        $responseInfo = curl_getinfo($ch);
        curl_close($ch);
        if (in_array($responseInfo['http_code'], $errorCodes)) {
            throw new Exception("Exception: 'Robin returned status code " . $responseInfo['http_code']."'");
        }

        $this->logger->debug($responseInfo);

        $this->logger->log("Robin returned status: " . $responseInfo['http_code']);
        return $responseInfo;
    }

    /**
     * Notify's the admin page that something went wrong.
     * @param string $message
     */
    function abort($message = "Something went wrong!"){
        $this->logger->log($message);
        Mage::getSingleton('adminhtml/session')->addWarning('Unable to send changes to ROBIN, see the log file for more information.');
    }

} 