<?php

namespace AlifShop\AlifShop\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Approve extends Action implements CsrfAwareActionInterface
{
    protected $resultJsonFactory;
    protected $orderFactory;
    protected $scopeConfig;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        OrderFactory $orderFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderFactory = $orderFactory;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    protected function verifyToken($token)
    {
        $secretToken = $this->scopeConfig->getValue('alifshop/webhook_secret', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return $token === $secretToken;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $orderId = $this->getRequest()->getParam('order_id');
        $token = $this->getRequest()->getHeader('Cashbox-token');

        if (!$this->verifyToken($token)) {
            return $result->setData(['success' => false, 'message' => __('Invalid token.')]);
        }

        if ($orderId) {
            $order = $this->orderFactory->create()->loadByIncrementId($orderId);

            if ($order->getId()) {
                $order->setState(Order::STATE_PROCESSING)
                    ->setStatus(Order::STATE_PROCESSING)
                    ->save();

                return $result->setData(['success' => true, 'message' => __('Order has been approved and is now processing.')]);
            }
        }

        return $result->setData(['success' => false, 'message' => __('Invalid order ID.')]);
    }
}
