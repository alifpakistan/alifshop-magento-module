<?php
namespace AlifShop\AlifShop\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Sales\Model\OrderFactory;

class Result extends Action
{
    protected $resultPageFactory;
    protected $resultRedirectFactory;
    protected $orderFactory;

    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        PageFactory $resultPageFactory,
        RedirectFactory $resultRedirectFactory
    ) {
        parent::__construct($context);
        $this->orderFactory = $orderFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
    }

    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order');
        $success = $this->getRequest()->getParam('success');

        if ($orderId === null || $success === null) {
            return $this->resultRedirectFactory->create()->setPath('/');
        }

        $incrementId = base64_decode($orderId);
        $order = $this->orderFactory->create()->loadByIncrementId($incrementId);
        if(!$order->getId()) {
            return $this->resultRedirectFactory->create()->setPath('/');
        }

        $resultPage = $this->resultPageFactory->create();
        // Set the title of the page
        $resultPage->getConfig()->getTitle()->set(__('AlifShop Payment'));
        return $resultPage;
    }
}