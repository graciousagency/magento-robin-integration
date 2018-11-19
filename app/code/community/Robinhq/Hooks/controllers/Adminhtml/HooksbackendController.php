<?php


/**
 * Class Robinhq_Hooks_Adminhtml_HooksbackendController
 */
class Robinhq_Hooks_Adminhtml_HooksbackendController extends Mage_Adminhtml_Controller_Action
{

    /** @var Robinhq_Hooks_Helper_Data */
    protected $helper;

    /**
     * Construct controller, set helper
     */
    protected function _construct()
    {
        parent::_construct();
        $this->helper = Mage::helper('robinhq_hooks');
    }

    /**
     * Before the index is rendered
     */
    protected function beforeRenderIndex()
    {
        $this->_title($this->__('Robin'));
        $block = $this->getLayout()
                ->createBlock('adminhtml/widget_button', 'massImportButton')
                ->setData([
                        'label' => Mage::helper('adminhtml')->__('Enqueue the Mass Sender'),
                        'onclick' => "setLocation('{$this->getUrl('*/hooksbackend/run')}')",
                ]);
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
     *
     * @return void
     */
    public function runAction()
    {
        $helper = $this->helper;

        if (!$this->helper->isEnabled()) {
            $message = 'Module is disabled. Please enable it first.';
            $helper->warnAdmin($message);
            $helper->log($message);
            $this->_redirect('*/hooksbackend/index');
            return;
        }

        $helper->log('Putting the Mass Sender action on the queue');

        /** @var Robinhq_Hooks_Model_Queue_Mass $massQueue */
        $massQueue = Mage::getModel('robinhq_hooks/queue_mass', $helper);

        $massQueue->setName('ROBIN Mass Send');
        $massQueue->enqueue();

        $helper->log('Done. Wait until the queue kicks in and handles these jobs');
        $helper->noticeAdmin('The Mass Send process is pushed to the queue.');

        $this->_redirect('*/hooksbackend/index');
    }

}