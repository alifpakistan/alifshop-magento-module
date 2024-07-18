<?php

namespace AlifShop\AlifShop\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Response\RedirectInterface;

class RedirectAfterOrder implements ObserverInterface
{
    protected $checkoutSession;
    protected $redirect;

    public function __construct(
        CheckoutSession $checkoutSession,
        RedirectInterface $redirect
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->redirect = $redirect;
    }

    public function execute(Observer $observer)
    {
        $redirectUrl = $this->checkoutSession->getRedirectUrl();
        if ($redirectUrl) {
            $this->checkoutSession->unsRedirectUrl();
            // $observer->getControllerAction()->getResponse()->setRedirect($redirectUrl);
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
}
