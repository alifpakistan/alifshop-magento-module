<?php
namespace AlifShop\AlifShop\Controller\Payment;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use AlifShop\AlifShop\Helper\Data as AlifShopHelper;

class UpdateOrder implements HttpPostActionInterface, CsrfAwareActionInterface
{
    const ORDER_SUCCESS_STATUS = "success";
    const ORDER_FAILURE_STATUS = "failure";
    protected $jsonFactory;
    protected $request;
    protected $response;
    protected $_helper;
    protected $orderFactory;

    public function __construct(
        JsonFactory $jsonFactory,
        RequestInterface $request,
        ResponseInterface $response,
        AlifShopHelper $_helper,
        OrderFactory $orderFactory,
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->response = $response;
        $this->_helper = $_helper;
        $this->orderFactory = $orderFactory;
    }

    protected function verifyToken($token)
    {
        $cashboxToken = $this->_helper->getAlifShopConfig("cashbox_token");
        return $token === $cashboxToken;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            // verify Token
            $token = $this->request->getHeader('Cashbox-token');
            if (!$this->verifyToken($token)) {
                throw new \Exception('Invalid token provided.');
            }

            // Get POST data
            $postData = $this->request->getPostValue();

            // Try to get JSON body if POST is empty
            if (empty($postData)) {
                $jsonBody = $this->request->getContent();
                $postData = json_decode($jsonBody, true);
            }

            if (
                empty($postData['id']) ||
                empty($postData['status']) ||
                !in_array(strtolower($postData['status']), [
                    self::ORDER_SUCCESS_STATUS,
                    self::ORDER_FAILURE_STATUS
                ])
            ) {
                throw new \InvalidArgumentException('No valid data received to process this request.');
            }

            // Process Order
            $orderId = $postData['id'];
            $orderStatus = $postData['status'];

            $order = $this->orderFactory->create()->loadByIncrementId($orderId);
            if (!$order->getId())
                throw new \Exception('Order not found with provided Order Id.');

            if (strtolower($orderStatus) === self::ORDER_SUCCESS_STATUS) {
                $order
                    ->setState(Order::STATE_PROCESSING)
                    ->setStatus(Order::STATE_PROCESSING)
                    ->save();
                $this->_helper->addCommentToOrder($order, 'Payment approved by AlifShop.');
                return $result->setData(['success' => true, 'message' => __('Order has been approved and is now processing.')]);
            }

            if (strtolower($orderStatus) === self::ORDER_FAILURE_STATUS) {
                $order->setState(Order::STATE_CANCELED)
                    ->setStatus(Order::STATE_CANCELED)
                    ->save();
                $this->_helper->addCommentToOrder($order, 'Payment canceled by AlifShop.');
                return $result->setData(['success' => true, 'message' => __('Order has been canceled.')]);
            }

        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}