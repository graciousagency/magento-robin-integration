<?php

/**
 * A small wrapper to communicate with the Robin API.
 * Class Robinhq_Hooks_Model_Api
 */
class Robinhq_Hooks_Model_Api
{

    /**
     * @var Robinhq_Hooks_Model_Logger
     */
    private $logger;

    /**
     * Gets and sets the dependency's
     * @param Robinhq_Hooks_Model_Logger $logger
     */
    public function __construct(Robinhq_Hooks_Model_Logger $logger)
    {
        $this->logger = $logger;
    }

    public function customer($customer)
    {
        return $this->customers(array($customer));
    }

    public function order($order)
    {
        return $this->orders(array($order));
    }

    /**
     * Makes from an array of Magento customers an array of Robinhq_Hooks_Model_RobinCustomer's
     * and sends it to the Robin API.
     *
     * @param $customers
     * @return mixed
     */
    public function customers($customers)
    {
        $this->logger->log("Sending customers to ROBIN");
        return $this->post('customers', $customers);
    }

    /**
     * Makes from an array of Magento orders an array of Robinhq_Hooks_Model_RobinOrder's
     * and sends it to the Robin API.
     *
     * @param $orders
     * @return mixed
     */
    public function orders($orders)
    {
        $this->logger->log("Sending orders to ROBIN");
        return $this->post("orders", $orders);
    }

    /**
     * Sets up the CURl request by loading the settings file and preparing the headers with
     * basic auth.
     * Throws exception when settings file is not found
     * @param $request
     * @throws Exception
     * @return resource
     */
    private function setUpCurl($request)
    {
        $config = Mage::getStoreConfig('settings/general');
        if (!empty($config['api_key']) && !empty($config['api_secret'])) {
            $url = $config['api_url'] . '/' . $request;
            $this->logger->log("Posting to [" . $url . "]");
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_USERPWD, $config['api_key'] . ":" . $config['api_secret']);

            return $ch;
        }
        throw new Exception(
            'Missing API configuration, go to System -> Configuration -> ROBINHQ -> Settings and fill in your API credentials'
        );
    }

    /**
     * Posts the values as a json string to the Robin API endpoint given in $request.
     *
     * @param $request
     * @param $values
     * @return mixed
     * @throws Exception
     * @throws Robinhq_Hooks_Model_Exception_BadRequestException
     * @throws Robinhq_Hooks_Model_Exception_RateLimitReachedException
     * @throws Robinhq_Hooks_Model_Exception_RequestImpossibleException
     * @throws Robinhq_Hooks_Model_Exception_UnauthorizedException
     * @throws Robinhq_Hooks_Model_Exception_UnknownStatusCodeException
     */
    private function post($request, $values)
    {
        $errorCodes = $this->getErrorCodes();

        $ch = $this->prepare($request, $values);

        $responseInfo = $this->execute($ch);

        $this->logger->log("Robin returned status: " . $responseInfo['http_code']);

        if (in_array($responseInfo['http_code'], $errorCodes)) {
            $error = Robinhq_Hooks_Model_Exception_RequestFailed::factory($responseInfo['http_code']);
            $this->logger->log($error->getMessage());
            throw $error;
        }


        return $responseInfo;
    }

    /**
     * @param $ch
     * @return mixed
     * @throws Robinhq_Hooks_Model_Exception_RequestImpossibleException
     */
    private function execute($ch)
    {
        if (curl_exec($ch) === false) {
            curl_close($ch);
            throw new Robinhq_Hooks_Model_Exception_RequestImpossibleException(
                "Error: 'Unable to preform request to Robin, request was not executed.'"
            );
        }

        $responseInfo = curl_getinfo($ch);
        curl_close($ch);
        return $responseInfo;
    }

    /**
     * @param $request
     * @param $values
     * @return resource
     * @throws Exception
     */
    private function prepare($request, $values)
    {
        $values = json_encode(array($request => $values));
        $ch = $this->setUpCurl($request);
        $valuesLength = strlen($values);
        $this->logger->log("Posting with: " . $values);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Content-Length: ' . $valuesLength
            )
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $values);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        return $ch;
    }

    /**
     * @return array
     */
    private function getErrorCodes()
    {
        $codes = new Robinhq_Hooks_Model_Robin_StatusCode();

        $errorCodes = array(
            $codes::INTERNAL_SERVER_ERROR,
            $codes::BAD_REQUEST,
            $codes::UNAUTHORIZED,
            $codes::RATE_LIMIT_EXCEEDED
        );
        return $errorCodes;
    }

} 