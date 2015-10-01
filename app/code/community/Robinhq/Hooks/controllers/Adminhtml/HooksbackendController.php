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
    private function isEnabled()
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
                    'label' => Mage::helper('adminhtml')->__('Enqueue the Mass Sender'),
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
     * It puts the process of retrieving all the customers and orders from the database
     * and converting them to objects the ROBIN API can understand on the queue. This process
     * will start as soon as your queue starts processing jobs. The moment your queue initiates
     * depends on your cron settings. Please, be sure to enable cron.
     */
    public function runAction()
    {
        if ($this->isEnabled()) {
            $this->helper->log("Putting the Mass Sender action on the queue");
            $massQueue = new Robinhq_Hooks_Model_Queue_Mass($this->helper);
            $massQueue->setName("ROBIN Mass Send");
            $massQueue->enqueue();
            $this->helper->log("Done. Wait until the queue kicks in and handles this jobs");
            $this->helper->noticeAdmin("The Mass Send process is pushed to the queue.");
        } else {
            $message = "Module is disabled. Please enable it first.";
            $this->helper->warnAdmin($message);
            $this->helper->log($message);
        }
        $this->_redirect('*/adminhtml_hooksbackend/index');
    }
}