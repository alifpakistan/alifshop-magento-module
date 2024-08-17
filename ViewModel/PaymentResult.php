<?php
namespace AlifShop\AlifShop\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order\Address\Renderer;

class PaymentResult implements ArgumentInterface
{
    protected $orderFactory;
    protected $request;
    protected $addressRenderer;

    public function __construct(
        OrderFactory $orderFactory,
        RequestInterface $request,
        Renderer $addressRenderer
    ) {
        $this->orderFactory = $orderFactory;
        $this->request = $request;
        $this->addressRenderer = $addressRenderer;
    }

    public function getOrder()
    {
        $incrementId = base64_decode($this->request->getParam('order'));
        $order = $this->orderFactory->create()->loadByIncrementId($incrementId);
        return $order->getId() ? $order : null;
    }

    public function isSuccess()
    {
        return $this->request->getParam('success');
    }

    public function getIncrementId()
    {
        return base64_decode($this->request->getParam('order'));
    }

    public function getFormattedShippingAddress($order)
    {
        if ($order && $order->getShippingAddress()) {
            return $this->addressRenderer->format($order->getShippingAddress(), 'html');
        }
        return '';
    }

    public function getFormattedBillingAddress($order)
    {
        if ($order && $order->getBillingAddress()) {
            return $this->addressRenderer->format($order->getBillingAddress(), 'html');
        }
        return '';
    }
}