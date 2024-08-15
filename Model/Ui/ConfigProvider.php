<?php

namespace AlifShop\AlifShop\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use AlifShop\AlifShop\Helper\Data as AlifShopHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;

class ConfigProvider implements ConfigProviderInterface
{
    protected $methodCode = 'alifshop';
    protected $scopeConfig;
    protected $curl;
    protected $_helper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Curl $curl,
        AlifShopHelper $_helper,
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->curl = $curl;
        $this->_helper = $_helper;
    }

    public function getConfig() {
        $instructions = $this->scopeConfig->getValue('payment/alifshop/instructions', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $minOrderTotal = $this->getMinOrderTotal();

        return [
            'payment' => [
                'instructions' => [
                    $this->methodCode => $instructions,
                ],
            ],
            $this->methodCode => [
                'min_order_total' => $minOrderTotal
            ]
        ];
    }

    protected function getMinOrderTotal()
    {
        return $this->_helper->getMinOrderTotal() / 100;
    }
}
