<?php

/**
 * Class Robinhq_Hooks_Adminhtml_HooksbackendController
 */
class Robinhq_Hooks_Adminhtml_HooksbackendController extends Mage_Adminhtml_Controller_Action
{

    /**
     * @var Robinhq_Hooks_Helper_Data
     */
    private $helper;

    /**
     * Sets up $this->helper
     */
    private function init()
    {
        $this->helper = Mage::helper('hooks');
        $config = Mage::getStoreConfig('settings/general');
        return $config['enabled'];
    }

    /**
     * Before the index is rendered
     */
    private function beforeRenderIndex()
    {
        $this->_title($this->__("Robin"));
        $block = $this->getLayout()
            ->createBlock('adminhtml/widget_button', 'massImportButton')
            ->setData(
                array(
                    'label' => Mage::helper('adminhtml')->__('Run'),
                    'onclick' => "setLocation('{$this->getUrl('*/adminhtml_hooksbackend/run')}')"
                )
            );
        $this->_addContent($block);
        $this->_setActiveMenu('robinhq/hooks');
    }


    public function indexAction()
    {
        $this->loadLayout();
        $this->beforeRenderIndex();
        $this->renderLayout();
    }

    /**
     * Runs when the user clicks the 'run' button on the page.
     * Sends all customers and orders to Robin.
     */
    public function runAction()
    {
        if ($this->init()) {
            $this->helper->log("Robin Mass Sender started queueing up customers and orders");
            try {
                $this->helper->sendCustomers();
                $this->helper->sendOrders();
                $this->helper->log(
                    "Robin Mass Sender finished building the queue. Wait unitll the queue kicks in and
            handles the jobs"
                );
                $this->helper->noticeAdmin(
                    "All customers and orders are send to the queue. Depending on your cron
            settings, they'll soon be send to ROBIN"
                );
            } catch (Exception $e) {
                $this->helper->warnAdmin($e->getMessage());
                $this->helper->log("Mass send failed with message: " . $e->getMessage());
            }
        } else {
            $message = "Module is disabled. Please enable it first.";
            $this->helper->warnAdmin($message);
            $this->helper->log($message);
        }
        $this->_redirect('*/adminhtml_hooksbackend/index');
    }
}