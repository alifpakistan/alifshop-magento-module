<?php

declare(strict_types=1);

namespace AlifShop\AlifShop\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class RedirectAfterOrder implements ObserverInterface
{
    /**
     * @var CheckoutSession
     */
    private CheckoutSession $checkoutSession;

    /**
     * @var ResponseInterface
     */
    private ResponseInterface $response;

    /**
     * @param CheckoutSession $checkoutSession
     * @param ResponseInterface $response
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        ResponseInterface $response
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->response = $response;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer): void
    {
        $redirectUrl = $this->checkoutSession->getRedirectUrl();
        if ($redirectUrl) {
            $this->checkoutSession->unsRedirectUrl();
            $this->response->setRedirect($redirectUrl);
        }
    }
}