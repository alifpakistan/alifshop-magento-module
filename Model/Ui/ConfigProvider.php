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

    protected function getMinOrderTotal() {

        $apiEndpoint = $this->getApiEndpoint() . "/merchant";
        $cashboxToken = $this->getCashboxToken();

        if(!$apiEndpoint && !$cashboxToken) return null;

        $this->curl->addHeader('Content-Type', 'application/json');
        $this->curl->addHeader('Accept', 'application/json');
        $this->curl->addHeader('Cashbox-token', $cashboxToken);
        $this->curl->get($apiEndpoint);

        // Get the response
        $response = $this->curl->getBody();

        // Convert the response to an array
        $responseArray = json_decode($response, true);

        return (isset($responseArray['min_installment_amount']) && $responseArray['min_installment_amount'])
                ? $responseArray['min_installment_amount'] / 100
                : null;
    }

    protected function getApiEndpoint() {
        return $this->_helper->getAlifShopConfig("api_endpoint");
    }

    protected function getCashboxToken() {
        return $this->_helper->getAlifShopConfig("cashbox_token");
    }
}
