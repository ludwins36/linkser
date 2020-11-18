<?php

namespace Vexsoluciones\Linkser\Controller\Process;

use Exception;
use Magento\Cms\Helper\Page;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;
use Vexsoluciones\Linkser\Helper\Config;

/**
 * Class Response
 *
 * @package Vexsoluciones\Linkser\Controller\Process
 */
class Response extends Action
{
    /**
     * @var string
     */
    const SUCCESS_STATUS = '00';

    /**
     * @var \Vexsoluciones\Linkser\Helper\Config
     */
    protected $config;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $frontendUrlBuilder;

    /**
     * @var \Magento\Cms\Helper\Page
     */
    protected $pageHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Response constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Vexsoluciones\Linkser\Helper\Config $config
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Framework\UrlInterface $frontendUrlBuilder
     * @param \Magento\Cms\Helper\Page $pageHelper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        Config $config,
        OrderFactory $orderFactory,
        UrlInterface $frontendUrlBuilder,
        Page $pageHelper,
        LoggerInterface $logger
    ) {

        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->config = $config;
        $this->orderFactory = $orderFactory;
        $this->frontendUrlBuilder = $frontendUrlBuilder;
        $this->pageHelper = $pageHelper;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Process response from linkser gateway
     *
     * @return mixed
     */
    public function execute()
    {
        print_r('hola');
        exit();
        try {
            $paymentResponse = $this->getRequest()->getPost('paymentResponse');
            $reponsePayLoadXML = base64_decode($paymentResponse);
            $xmlObject = simplexml_load_string($reponsePayLoadXML);
            if ($xmlObject) {
                $payloadxml = base64_decode($xmlObject->payload);
                $signaturexml = $xmlObject->signature;
                $secretKey = $this->config->getSecretKey();
                $base64EncodedPayloadResponse = base64_encode($payloadxml);
                $signatureHash = strtoupper(hash_hmac('sha256', $base64EncodedPayloadResponse ,$secretKey, false));
                $responseData = simplexml_load_string($payloadxml);
                $responseCode = (string)$responseData->respCode;
                $orderId = (string)$responseData->uniqueTransactionCode;
                $order = $this->orderFactory->create()->load($orderId, 'increment_id');

                if ($this->config->isAllowDebug()) {
                    $this->logger->info(__('Response linkser for order id %1', $order->getId()));
                    $this->logger->info(__('Response code: %1', $responseCode));
                    $this->logger->info($payloadxml);
                }

                if ($order->getId()) {
                    if ($responseCode == self::SUCCESS_STATUS && $signaturexml == $signatureHash) {
                        $transactionId = (string)$responseData->tranRef;
                        $order->getPayment()->addData(['cc_trans_id' => $transactionId])->save();
                        $order->getPayment()->getMethodInstance()->capture($order->getPayment(), $order->getGrandTotal());
                        $redirectUrl = $this->frontendUrlBuilder->setScope($order->getStoreId())->getUrl('checkout/onepage/success');
                        return $this->resultRedirectFactory->create()->setUrl($redirectUrl);
                    } else {
                        $redirectUrl = $this->frontendUrlBuilder->setScope($order->getStoreId())->getUrl('checkout/onepage/failure');
                        return $this->resultRedirectFactory->create()->setUrl($redirectUrl);
                    }
                }
            }
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        if ($checkoutFailPage = $this->config->getCheckoutFailurePage()) {
            $redirectUrl = $this->pageHelper->getPageUrl($checkoutFailPage);
            return $this->resultRedirectFactory->create()->setUrl($redirectUrl);
        }

        return $this->resultRedirectFactory->create()->setPath('checkout/onepage/failure');
    }
}
