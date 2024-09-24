<?php

namespace AlifShop\AlifShop\Controller\Check;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use AlifShop\AlifShop\Model\PriceChecker;

class SpecialPrice extends Action
{
    protected $resultJsonFactory;
    protected $priceChecker;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        PriceChecker $priceChecker
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->priceChecker = $priceChecker;
    }

    public function execute()
    {
        $hasSpecialPrice = $this->priceChecker->hasSpecialPrice();
        return $this->resultJsonFactory->create()->setData(['has_special_price' => $hasSpecialPrice]);
    }
}