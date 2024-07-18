<?php

namespace AlifShop\AlifShop\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;

class ConfigProvider implements ConfigProviderInterface
{
    protected $methodCode = 'alifshop';
    protected Curl $curl;
    protected $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Curl $curl
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->curl = $curl;
    }

    public function getConfig()
    {
        $instructions = $this->scopeConfig->getValue('payment/alifshop/instructions', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $minOrderTotal = 50;

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
        return $this->scopeConfig->getValue('payment/alifshop/api_endpoint', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    protected function getCashboxToken() {
        return $this->scopeConfig->getValue('payment/alifshop/cashbox_token', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}
