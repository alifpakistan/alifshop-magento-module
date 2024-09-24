<?php

namespace AlifShop\AlifShop\Model;

use Magento\Checkout\Model\Session as CheckoutSession;

class PriceChecker
{
    protected $checkoutSession;

    public function __construct(CheckoutSession $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    public function hasSpecialPrice()
    {
        $quote = $this->checkoutSession->getQuote();
        $currentDate = new \DateTime(); // Get the current date

        foreach ($quote->getAllItems() as $item) {
            $product = $item->getProduct();
            $specialPrice = $product->getSpecialPrice();
            $price = $product->getPrice();

            // Check if special price exists and is less than the regular price
            if ($specialPrice && $specialPrice < $price) {
                // Get special price date range
                $specialFromDate = $product->getSpecialFromDate() ? new \DateTime($product->getSpecialFromDate()) : null;
                $specialToDate = $product->getSpecialToDate() ? new \DateTime($product->getSpecialToDate()) : null;

                // Validate special price based on the date range
                if (($specialFromDate === null || $currentDate >= $specialFromDate) &&
                    ($specialToDate === null || $currentDate <= $specialToDate)) {
                    return true; // Special price is valid
                }
            }
        }
        return false; // No valid special price found
    }
}