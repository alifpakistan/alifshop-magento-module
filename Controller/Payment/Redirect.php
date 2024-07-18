<?php

namespace AlifShop\AlifShop\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Checkout\Model\Session as CheckoutSession;

class Redirect extends Action
{
    protected $checkoutSession;

    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
    }

    public function execute()
    {
        // Retrieve the redirect URL from the checkout session
        $redirectUrl = $this->checkoutSession->getRedirectUrl();

        // Clear the redirect URL from the session
        $this->checkoutSession->unsRedirectUrl();

        if ($redirectUrl) {
            // Create a redirect result
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($redirectUrl);
            return $resultRedirect;
        } else {
            // Handle the error case where no redirect URL is found
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('checkout/cart'); // Redirect to cart or any other fallback page
            return $resultRedirect;
        }
    }
}
