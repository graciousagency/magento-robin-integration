<?php


/**
 * Class Robinhq_Hooks_Model_RobinOrder
 */
class Robinhq_Hooks_Model_Robin_Order
{

    /** @var Robinhq_Hooks_Helper_Data */
    protected $helper;

    /**
     * Factory method for creating an array with data that is
     * required by the Robin API. Also adds a example 'details_view' key
     * listing the products and shipments. To add extra fields, read up on
     * the documentation on http://docs.robinhq.com/faq/robin-api/ how to format.
     *
     * Extra details_view's can be added inside the getDetailsView method.
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function factory(Mage_Sales_Model_Order $order)
    {
        /** @var Robinhq_Hooks_Helper_Data $helper */
        $this->helper = Mage::helper('robinhq_hooks');

        $data = $this->getBaseInfo($order);
        $data['details_view'] = $this->getDetailsView($order);

        return $data;
    }

    /**
     * Wrapper method for generating all the child elements for the 'details_view'
     * key. If you want to add your own 'details_view' field, write a method for it
     * and add a call to it here like: $infoName = $this->getInfos();. Then add
     * $infoName to the array that's returned.
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function getDetailsView(Mage_Sales_Model_Order $order)
    {
        $data = new Varien_Object([
                $this->getDetails($order),
                $this->getProductsOverview($order),
                $this->getShipments($order),
                $this->getInvoices($order),
        ]);
        Mage::dispatchEvent('robin_hooks_order_details', [
                'order' => $this,
                'data' => $data,
        ]);
        return $data->toArray();
    }

    /**
     * Gets the base info for a order, contains the required parts
     * for the Robin API.
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function getBaseInfo(Mage_Sales_Model_Order $order)
    {
        $date = $order->getCreatedAt();
        $orderByDate = Mage::getModel('core/date')
                ->date('Y/m/d', strtotime($date));

        return [
                "order_number" => $order->getIncrementId(),
                "email_address" => $order->getCustomerEmail(),
                "url" => $this->getOrderAdminUrl($order),
                "order_by_date" => $orderByDate,
                "revenue" => $order->getBaseGrandTotal(),
                "list_view" => [
                        "order_number" => $order->getIncrementId(),
                        "date" => $date,
                        "status" => $order->getStatus(),
                ],
        ];
    }

    /**
     * Gets the Details view as first item of the 'details_view' key
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function getDetails(Mage_Sales_Model_Order $order)
    {
        $payment = $order->getPayment()
                ->getMethodInstance();
        $paymentStatus = $payment->getStatus();

        return [
                "display_as" => "details",
                "data" => [
                        "date" => $order->getCreatedAt(),
                        "status" => $order->getStatus(),
                        "payment_method" => $payment->getTitle(),
                        "payment_status" => (null === $paymentStatus) ? "" : $paymentStatus,
                ],
        ];
    }

    /**
     * Gets all products of the order. Displays the name, quantity, price and status.
     * Also gets shipment and payment info to generate a total view of the order.
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function getProductsOverview(Mage_Sales_Model_Order $order)
    {
        return [
                "display_as" => 'columns',
                "caption" => 'products',
                "data" => [
                        $this->getProducts($order),
                        $this->getShipmentInfo($order),
                        $this->getOrderTotalInfo($order)
                ],
        ];
    }

    /**
     * When there are shipments, it lists them and their status.
     * Otherwise the 'data' key stays empty.
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function getShipments(Mage_Sales_Model_Order $order)
    {
        $base = [
                "display_as" => 'rows',
                "caption" => 'shipments',
                "data" => [],
        ];
        if (!$order->hasShipments()) {
            return $base;
        }

        $orderStatus = $order->getState();

        /** @var Mage_Sales_Model_Order_Shipment $shipment */
        foreach ($order->getShipmentsCollection() as $shipment) {
            $base['data'][] = [
                    "Shipment:" => "<a target='_blank' href='" . $this->getShipmentUrl($shipment) . "'>"
                            . $shipment->getIncrementId() . "</a>",
                    "Status:" => ($orderStatus === $order::STATE_COMPLETE) ? "Shipped" : "Processing",
            ];
        }
        return $base;
    }

    /**
     * When there are invoices it lists them with a link, status and the price.
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function getInvoices(Mage_Sales_Model_Order $order)
    {
        $base = [
                "display_as" => 'rows',
                "caption" => 'invoices',
                "data" => [],
        ];

        if (!$order->hasInvoices()) {
            return $base;
        }

        /** @var Mage_Sales_Model_Order_Invoice $invoice */
        foreach ($order->getInvoiceCollection() as $invoice) {
            $base['data'][] = [
                    "Invoice:" => "<a target='_blank' href='" . $this->getInvoiceAdminUrl($invoice) . "'>"
                            . $invoice->getIncrementId() . "</a>",
                    "Status:" => $this->getInvoiceStatus($invoice),
                    "Price:" => $this->helper->formatPrice($invoice->getBaseGrandTotal()),
            ];
        }

        return $base;
    }

    /**
     * Get products on order
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function getProducts(Mage_Sales_Model_Order $order)
    {
        $products = [];

        $taxHelper = Mage::helper('tax');
        // All products from this order
        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllItems() as $item) {
            $price = $taxHelper->getPrice($item, $item->getPrice(), true, null, null, null, null, false);
            $products[] = [
                    "Product" => $item->getName(),
                    "Quantity" => (int)$item->getQtyOrdered(),
                    "Price" => $this->helper->formatPrice($price),
            ];
        }

        return $products;
    }

    /**
     * Get order shipment info
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function getShipmentInfo(Mage_Sales_Model_Order $order)
    {
        return [
                "Product:" => "Shipment",
                "Quantity:" => '',
                "Price:" => $this->helper->formatPrice($order->getBaseShippingAmount()),
        ];
    }

    /**
     * Get order total info
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function getOrderTotalInfo(Mage_Sales_Model_Order $order)
    {
        return [
                "Product" => "Total",
                "quantity" => '',
                "price" => $this->helper->formatPrice($order->getBaseGrandTotal()),
        ];
    }

    /**
     * Get order admin url
     *
     * @param Mage_Sales_Model_Order $order
     * @return string
     */
    protected function getOrderAdminUrl(Mage_Sales_Model_Order $order)
    {
        return Mage::helper('robinhq_hooks')
                ->getUrl('adminhtml/sales_order/view', [
                        'order_id' => $order->getId(),
                        '_type' => Mage_Core_Model_Store::URL_TYPE_WEB,
                ]);
    }

    /**
     * Get shipment url
     *
     * @param Mage_Sales_Model_Order_Shipment $shipment
     * @return string
     */
    protected function getShipmentUrl(Mage_Sales_Model_Order_Shipment $shipment)
    {
        return Mage::helper('robinhq_hooks')
                ->getUrl('adminhtml/sales_shipment/view', [
                        'shipment_id' => $shipment->getId(),
                        '_type' => Mage_Core_Model_Store::URL_TYPE_WEB,
                ]);
    }

    /**
     * Get invoice status
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @return string
     */
    protected function getInvoiceStatus(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $stateCode = $invoice->getState();
        switch ($stateCode) {
            case $invoice::STATE_CANCELED:
                $state = "Canceled";
                break;
            case $invoice::STATE_PAID:
                $state = "Paid";
                break;
            default:
                $state = 'Open';
                break;
        }
        return $state;
    }


    /**
     * Get invoice admin url
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @return string
     */
    protected function getInvoiceAdminUrl(Mage_Sales_Model_Order_Invoice $invoice)
    {
        return Mage::helper('robinhq_hooks')
                ->getUrl('adminhtml/sales_invoice/view', [
                        'invoice_id' => $invoice->getId(),
                        '_type' => Mage_Core_Model_Store::URL_TYPE_WEB,
                ]);
    }

}