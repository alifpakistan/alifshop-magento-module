<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AlifShop\AlifShop\Model\Payment;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Framework\DataObject;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use AlifShop\AlifShop\Helper\Data as AlifShopHelper;

class AlifShop extends AbstractMethod
{
    protected $_code = "alifshop";
    protected $_canAuthorize = true;
    protected $_isOffline = true;
    protected CheckoutSession $checkoutSession;
    protected OrderInterface $order;
    protected Curl $curl;
    protected ScopeConfigInterface $scopeConfig;
    protected AlifShopHelper $_helper;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        CheckoutSession $checkoutSession,
        OrderInterface $order,
        Curl $curl,
        AlifShopHelper $_helper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->checkoutSession = $checkoutSession;
        $this->order = $order;
        $this->curl = $curl;
        $this->scopeConfig = $scopeConfig;
        $this->_helper = $_helper;
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null): bool
    {

        if (!$this->getConfigData('active') || !$this->getConfigData('cashbox_token')) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount): void
    {
        if (!$this->canAuthorize()) {
            throw new LocalizedException(__('The authorize action is not available.'));
        }

        $order = $payment->getOrder();
        $orderData = $this->getOrderData($order);

        try {
            // Make API call to authorize the payment
            $apiResponse = $this->makeApiCallToAuthorize($orderData);

            if (isset($apiResponse["invoice_id"]) && $apiResponse["invoice_id"]) {
                // Save transaction details
                $payment->setTransactionId($apiResponse['invoice_id']);
                $payment->setIsTransactionClosed(0);

                // Save redirect URL in the checkout session
                $this->checkoutSession->setRedirectUrl($apiResponse['redirect_url']);
                return;

            } else {
                $errorMessages = [$apiResponse['message']];

                // Check if there are errors in the response
                if (isset($apiResponse['errors']) && is_array($apiResponse['errors'])) {
                    // Iterate through the errors and concatenate the messages
                    foreach ($apiResponse['errors'] as $field => $messages) {
                        foreach ($messages as $message) {
                            $errorMessages[] = $message;
                        }
                    }
                }
                throw new LocalizedException(__(implode(", ", $errorMessages )));
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__('Error occurred while processing your request: %1', $e->getMessage()));
        }
    }

    protected function getOrderData($order): array
    {
        // Build the data array for the API call
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();
        $items = $order->getAllVisibleItems();

        $data = [
            'order' => [
                'id' => $order->getIncrementId()
            ],
            'customer' => [
                'id' => $order->getCustomerId() ?? 1,
                'first_name' => $order->getCustomerFirstname(),
                'last_name' => $order->getCustomerLastname(),
                'email' => $order->getCustomerEmail(),
                'phone_number' => $shippingAddress->getTelephone(),
            ],
            'shipping_address' => [
                'first_name' => $shippingAddress->getFirstname(),
                'last_name' => $shippingAddress->getLastname(),
                'street' => implode(' ', $shippingAddress->getStreet()),
                'city' => $shippingAddress->getCity(),
                'region' => $shippingAddress->getRegion(),
                'postcode' => $shippingAddress->getPostcode(),
                'country_id' => $shippingAddress->getCountryId(),
                'phone_number' => $shippingAddress->getTelephone(),
            ],
            'billing_address' => [
                'first_name' => $billingAddress->getFirstname(),
                'last_name' => $billingAddress->getLastname(),
                'street' => implode(' ', $billingAddress->getStreet()),
                'city' => $billingAddress->getCity(),
                'region' => $billingAddress->getRegion(),
                'postcode' => $billingAddress->getPostcode(),
                'country_id' => $billingAddress->getCountryId(),
                'phone_number' => $billingAddress->getTelephone(),
            ],
            'cart' => [
                'id' => $order->getQuoteId(),
                'items_count' => count($items),
                'items_quantity' => $order->getTotalQtyOrdered(),
                'subtotal' => $order->getSubtotal() * 100,
                "currency_code" => $this->_helper->getCurrentCurrencyCode(),
                'grand_total' => $order->getGrandTotal() * 100,
            ],
            'items' => [],
        ];

        foreach ($items as $item) {
            if(!$item->getPrice()) continue;
            $data['items'][] = [
                'item_id' => $item->getProductId(),
                'product_id' => $item->getProductId(),
                'img_url' => $this->_helper->getOrderItemImageUrl($item),
                'name' => $item->getName(),
                'quantity' => $item->getQtyOrdered(),
                'price' => $item->getPrice() * 100,
                "final_price" => $item->getPrice() * 100,
                "tax_amount" => $item->getTaxAmount(),
                "discount" => $item->getDiscountAmount(),
                "currency_code" => $this->_helper->getCurrentCurrencyCode(),
                'row_total' => $item->getRowTotal() * 100,
            ];
        }

        return $data;
    }

    protected function makeApiCallToAuthorize(array $orderData): array
    {
        // Get the API endpoint and token from the configuration
        $apiEndpoint = $this->getConfigData('api_endpoint') . "/invoice/web/";
        $cashboxToken = $this->getConfigData('cashbox_token');
        $orderSuccessUrl = $this->getConfigData('order_success_url');

        $apiPayload = [
            ...$orderData,
            "callback_url" => $this->_helper->getStoreUrl($orderSuccessUrl),
            "success_url" => $this->_helper->getStoreUrl("alifshop/payment/approve"),
            "fail_url" => $this->_helper->getStoreUrl("alifshop/payment/cancel"),
            "return_url" => $this->_helper->getStoreUrl($orderSuccessUrl),
            "source" => "magento",
            "plugin_version" => $this->_helper->getVersionInfo()
        ];

        // Convert order data to JSON
        $jsonData = json_encode($apiPayload);

        try {
            // Set up the cURL request
            $this->curl->addHeader('Content-Type', 'application/json');
            $this->curl->addHeader('Accept', 'application/json');
            $this->curl->addHeader('Cashbox-token', $cashboxToken);
            $this->curl->post($apiEndpoint, $jsonData);

            // Get the response
            $response = $this->curl->getBody();

            // Convert the response to an array
            $responseArray = json_decode($response, true);

            return $responseArray;
        } catch (\Exception $e) {
            throw new LocalizedException(__('An error occurred during the API call: %1', $e->getMessage()));
        }
    }
}
