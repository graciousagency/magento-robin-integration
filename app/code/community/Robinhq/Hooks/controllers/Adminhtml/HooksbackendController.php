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

    private function init(){
        $this->helper = Mage::helper('hooks');
    }

    private function beforeRenderIndex(){
        $this->_title($this->__("Robin"));
        $block = $this->getLayout()
            ->createBlock('adminhtml/widget_button', 'massImportButton')
            ->setData(array(
                    'label'     => Mage::helper('adminhtml')->__('Run'),
                    'onclick'   => "setLocation('{$this->getUrl('*/adminhtml_hooksbackend/run')}')"
                ));
        $this->_addContent($block);
        $this->_setActiveMenu('robinhq/hooks');
    }

    /**
     *
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->beforeRenderIndex();
        $this->renderLayout();
    }

    /**
     *
     */
    public function runAction()
    {
        $this->init();
        $this->helper->log("Robin Mass Importer Started");
        try{
            $this->helper->sendCustomers();
            $this->helper->sendOrders();
            $this->helper->log("Robin Mass Importer Finished");
            $this->helper->noticeAdmin("Successfully send all customers and orders");
        }
        catch(Exception $e){
            $this->helper->warnAdmin($e->getMessage());
            $this->helper->log("Mass send failed with message: ". $e->getMessage());
        }
        $this->_redirect('*/adminhtml_hooksbackend/index');
    }
}