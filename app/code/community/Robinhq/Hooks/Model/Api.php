<?php


/**
 * A small wrapper to communicate with the Robin API.
 * Class Robinhq_Hooks_Model_Api
 */
class Robinhq_Hooks_Model_Api
{

    /** @var Robinhq_Hooks_Helper_Data */
    protected $helper;

    /**
     * Gets and sets the dependency's
     * @param Robinhq_Hooks_Helper_Data $helper
     */
    public function __construct(Robinhq_Hooks_Helper_Data $helper)
    {
        $this->helper = $helper;
    }

    public function customer($customer)
    {
        return $this->customers([$customer]);
    }

    public function order($order)
    {
        return $this->orders([$order]);
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
        $this->helper->log('Sending customers to ROBIN');
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
        $this->helper->log('Sending orders to ROBIN');
        return $this->post('orders', $orders);
    }

    /**
     * Sets up the CURl request by loading the settings file and preparing the headers with
     * basic auth.
     * Throws exception when settings file is not found
     * @param $request
     * @throws Exception
     * @return resource
     */
    protected function setUpCurl($request)
    {
        $helper = $this->helper;

        $apiUrl = $helper->getConfig('api_url');
        $apiKey = $helper->getConfig('api_key');
        $apiSecret = $helper->getConfig('api_secret');

        if (empty($apiKey) || empty($apiSecret)) {
            throw new Exception('Missing API configuration, go to System -> Configuration -> ROBINHQ -> Settings and fill in your API credentials');
        }

        $url = $apiUrl . '/' . $request;

        $this->helper->log("Posting to [" . $url . "]");

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERPWD, $apiKey . ":" . $apiSecret);

        return $ch;
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
    protected function post($request, $values)
    {
        $errorCodes = $this->getErrorCodes();
        $ch = $this->prepare($request, $values);

        $responseInfo = $this->execute($ch);

        $this->helper->log("Robin returned status: " . $responseInfo['http_code']);
        if (in_array($responseInfo['http_code'], $errorCodes)) {

            /** @var Robinhq_Hooks_Model_Exception_RequestFailed $requestFailedFactory */
            $requestFailedFactory = Mage::getModel('robinhq_exception_RequestFailed');

            $error = $requestFailedFactory::factory($responseInfo['http_code']);

            $this->helper->log($error->getMessage());

            throw $error;
        }

        return $responseInfo;
    }

    /**
     * Execute request
     *
     * @param $ch
     * @return mixed
     * @throws Robinhq_Hooks_Model_Exception_RequestImpossibleException
     */
    protected function execute($ch)
    {
        if (false === curl_exec($ch)) {
            curl_close($ch);

            $error = Mage::getModel('robinhq_hooks/exception_requestImpossibleException',
                    "Error: 'Unable to preform request to Robin, request was not executed.'"
            );

            throw new $error;
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
    protected function prepare($request, $values)
    {

        // Start extra check to make sure we are not passing any customers or orders without email
        foreach ($values as $key => $value) {
            if (empty($value['email_address'])) {
                unset($values[$key]);
            }
        }
        // End extra email check

        $values = json_encode([$request => $values]);
        $valuesLength = strlen($values);

        $ch = $this->setUpCurl($request);

        $this->helper->log("Posting with: " . $values);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . $valuesLength,
        ]);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $values);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

//        // Debug request
//        curl_setopt($ch, CURLOPT_VERBOSE, true);
//        $fp = fopen(dirname(__FILE__) . '/robin_curl_output.txt', 'w');
//        curl_setopt($ch, CURLOPT_STDERR, $fp);

        return $ch;
    }

    /**
     * @return array
     */
    protected function getErrorCodes()
    {
        /** @var Robinhq_Hooks_Model_Robin_StatusCode $codes */
        $codes = Mage::getModel('robinhq_hooks/robin_statusCode');
        $errorCodes = [
                $codes::INTERNAL_SERVER_ERROR,
                $codes::BAD_REQUEST,
                $codes::UNAUTHORIZED,
                $codes::RATE_LIMIT_EXCEEDED,
        ];

        return $errorCodes;
    }

} 