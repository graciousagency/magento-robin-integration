<?php


/**
 * Class Robinhq_Hooks_Model_RobinOrder
 */
class Robinhq_Hooks_Model_Robin_Order
{

    /**
     * @var Mage_Sales_Model_Order
     */
    private $order;

    /**
     * @var Mage_Sales_Model_Resource_Order_Shipment_Collection
     */
    private $shipments;

    /**
     * @var Robinhq_Hooks_Helper_Data
     */
    private $helper;

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
        $robinOrder = new static;
        $robinOrder->helper = Mage::helper("hooks");
        $robinOrder->order = $order;
        $robinOrder->shipments = $robinOrder->order->getShipmentsCollection();
        return $robinOrder->make();
    }

    /**
     * Starts the construction of the robinOrder array by generating
     * the base and getting the details view.
     *
     * @return array
     */
    private function make()
    {
        $data = $this->getBaseInfo();
        $data['details_view'] = $this->getDetailsView();

        return $data;
    }

    /**
     * Wrapper method for generating all the child elements for the 'details_view'
     * key. If you want to add your own 'details_view' field, write a method for it
     * and add a call to it here like: $infoName = $this->getInfos();. Then add
     * $infoName to the array that's returned.
     *
     * @return array
     */
    private function getDetailsView()
    {
        $details = $this->getDetails();
        $productsOverview = $this->getProductsOverview();
        $shipments = $this->getShipments();
        $invoices = $this->getInvoices();
        return array($details, $productsOverview, $shipments, $invoices);
    }

    /**
     * Gets the base info for a order, contains the required parts
     * for the Robin API.
     * @return array
     */
    private function getBaseInfo()
    {
        $date = $this->order->getCreatedAt();
        $orderByDate = Mage::getModel('core/date')->date('Y/m/d', strtotime($date));
        return array(
            "order_number" => $this->order->getIncrementId(),
            "email_address" => $this->order->getCustomerEmail(),
            "url" => $this->getOrderAdminUrl(),
            "order_by_date" => $orderByDate,
            "list_view" => array(
                "order_number" => $this->order->getIncrementId(),
                "date" => $date,
                "status" => $this->order->getStatus(),
            ),
        );
    }

    /**
     * Gets the Details view as first item of the 'details_view' key
     * @return array
     */
    private function getDetails()
    {
        $payment = $this->order->getPayment()->getMethodInstance();
        $paymentStatus = $payment->getStatus();
        return array(
            "display_as" => "details",
            "data" => array(
                "date" => $this->order->getCreatedAt(),
                "status" => $this->order->getStatus(),
                "payment_method" => $payment->getTitle(),
                "payment_status" => ($paymentStatus === null) ? "" : $paymentStatus,
            )
        );
    }

    /**
     * Gets all products of the order. Displays the name, quantity, price and status.
     * Also gets shipment and payment info to generate a total view of the order.
     *
     * @return array
     */
    private function getProductsOverview()
    {
        $base = array(
            "display_as" => 'columns',
            "caption" => 'products',
            "data" => array()
        );

        $base['data'] = $this->getProducts($base);

        $base['data'][] = $this->getShipmentInfo();

        $base['data'][] = $this->getOrderTotalInfo();

        return $base;
    }

    /**
     * When there are shipments, it lists them and their status.
     * Otherwise the 'data' key stays empty.
     * @return array
     */
    private function getShipments()
    {
        $base = array(
            "display_as" => 'rows',
            "caption" => 'shipments',
            "data" => array()
        );
        $orderStatus = $this->order->getState();
        if ($this->shipments !== false) {
            foreach ($this->shipments as $shipment) {
                $url = $this->getShipmentUrl($shipment);
                $base['data'][] = array(
                    "Shipment:" => "<a target='_blank' href='" . $url . "'>" . $shipment->getIncrementId() . "</a>",
                    "Status:" => ($orderStatus === Mage_Sales_Model_Order::STATE_COMPLETE) ? "Shipped" : "Processing"
                );
            }
        }

        return $base;
    }

    /**
     * When there are invoices it lists them with a link, status and the price.
     *
     * @return array
     */
    private function getInvoices()
    {
        $base = array(
            "display_as" => 'rows',
            "caption" => 'invoices',
            "data" => array(),
        );

        if ($this->order->hasInvoices()) {
            foreach ($this->order->getInvoiceCollection() as $invoice) {
                $status = $this->getInvoiceStatus($invoice);
                $url = $this->getInvoiceAdminUrl($invoice);
                $base['data'][] = array(
                    "Invoice:" => "<a target='_blank' href='" . $url . "'>" . $invoice->getIncrementId() . "</a>",
                    "Status:" => $status,
                    "Price:" => $this->helper->formatPrice($invoice->getBaseGrandTotal())
                );
//                $this->helper->getLogger()->debug($invoice->getState());
            }
        }

        return $base;
    }

    /**
     * @return array
     */
    private function getProducts()
    {
        $products = array();
        $items = $this->order->getAllItems();
        //All products from this order
        foreach ($items as $item) {
            $products[] = array(
                "Product" => $item->getName(),
                "Quantity" => (int)$item->getQtyOrdered(),
                "Price" => $this->helper->formatPrice($item->getPrice())
            );
        }
        return $products;
    }

    /**
     * @return array
     */
    private function getShipmentInfo()
    {
        return array(
            "Product:" => "Shipment",
            "Quantity:" => '',
            "Price:" => $this->helper->formatPrice($this->order->getBaseShippingAmount()),
        );
    }

    /**
     * @return array
     */
    private function getOrderTotalInfo()
    {
        return array(
            "Product" => "Total",
            "quantity" => '',
            "price" => $this->helper->formatPrice($this->order->getBaseGrandTotal()),
        );
    }

    /**
     * @return mixed
     */
    private function getOrderAdminUrl()
    {
        return Mage::helper('adminhtml')->getUrl(
            'adminhtml/sales_order/view',
            array('order_id' => $this->order->getId(), '_type' => Mage_Core_Model_Store::URL_TYPE_WEB)
        );
    }

    /**
     * @param $shipment
     * @return mixed
     */
    private function getShipmentUrl($shipment)
    {
        $shipmentId = Mage::getModel('sales/order_shipment')
            ->loadByIncrementId($shipment->getIncrementId())
            ->getId();
        return Mage::helper('adminhtml')
            ->getUrl(
                'adminhtml/sales_shipment/view',
                array('shipment_id' => $shipmentId, '_type' => Mage_Core_Model_Store::URL_TYPE_WEB)
            );
    }

    /**
     * @param $invoice
     * @return string
     */
    private function getInvoiceStatus($invoice)
    {
        $stateCode = $invoice->getState();
        switch ($stateCode) {
            case Mage_Sales_Model_Order_Invoice::STATE_CANCELED:
                $state = "Canceled";
                break;
            case Mage_Sales_Model_Order_Invoice::STATE_PAID:
                $state = "Paid";
                break;
            default:
                $state = 'Open';
                break;
        }
        return $state;
    }


    /**
     * @param $invoice
     * @return string
     */
    private function getInvoiceAdminUrl($invoice)
    {
        return Mage::helper('adminhtml')
            ->getUrl(
                'adminhtml/sales_invoice/view',
                array('invoice_id' => $invoice->getId(), '_type' => Mage_Core_Model_Store::URL_TYPE_WEB)
            );
    }

}