<?php
/**
 * Copyright � 2015 Inchoo d.o.o.
 * created by Zoran Salamun(zoran.salamun@inchoo.net)
 */
namespace Vexsoluciones\Linkser\Controller\Payment;

use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Payment\Transaction;

class Data extends \Magento\Framework\App\Action\Action
{
	
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    /**
     * @var PaymentHelper
     */
    protected $_paymentHelper;
	
    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;
    /**
     * @var Transaction\BuilderInterface
     */
    protected $transactionBuilder;
	
	protected $resultJsonFactory;
	
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Psr\Log\LoggerInterface $logger,
        PaymentHelper $paymentHelper,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->_checkoutSession = $checkoutSession;
        $this->_logger = $logger;
        $this->_paymentHelper = $paymentHelper;
        $this->transactionRepository = $transactionRepository;
        $this->transactionBuilder = $transactionBuilder;
		$this->resultJsonFactory = $resultJsonFactory;

    }
	
    /**
     * Return checkout session object
     *
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCheckoutSession()
    {
        return $this->_checkoutSession;
    }
	
	public function execute()
    {
        $order = $this->_getCheckoutSession()->getLastRealOrder();
        
		$this->_logger->debug("TODOPAGO - FIRST STEP INIT");
        try {
            $method = $order->getPayment()->getMethod();
            $methodInstance = $this->_paymentHelper->getMethodInstance($method);
			$this->_logger->debug("TODOPAGO - FIRST STEP SDK");
			$res = $methodInstance->firstStep($order);
	
			if($methodInstance->getAmbiente() == "test") {                    
				$message = "Todo Pago (TEST): " . $res['StatusMessage'];                    
			} else {                  
				$message = "Todo Pago: " . $res['StatusMessage'];                  
			}
			
			$payment = $order->getPayment();
			$payment->setTransactionId($res["RequestKey"])->setIsTransactionClosed(0);
			
			$orderTransactionId = $payment->getTransactionId();
			$payment->setParentTransactionId($order->getId());
			$payment->setIsTransactionPending(true);
			
			$transaction = $this->transactionBuilder->setPayment($payment)
				->setOrder($order) 
				->setTransactionId($payment->getTransactionId())
				->build(Transaction::TYPE_ORDER);
			$payment->addTransactionCommentsToOrder($transaction, $message);

			$statuses = $methodInstance->getOrderStatuses();
			$status = $statuses["inicial"];
			$state = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
			$order->setState($state)->setStatus($status);
			$payment->setSkipOrderProcessing(true);
			$order->save();
			$this->_logger->debug("TODOPAGO - FIRST STEP REDIRECT");

			$hibrido = $this->scopeConfig->getValue('payment/todopago/hibrido', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			if($methodInstance->getCode() == "tpredirect" ) {
				$this->_redirect($res["URL_Request"]);
			}elseif($hibrido==0 AND $methodInstance->getCode() == "tpbille"){
				$url = $res["URL_Request"];

				$result = $this->resultJsonFactory->create();
				return $result->setData(['url' => $url]);

			} else {
				$url = $methodInstance->getCustomUrl();

				$result = $this->resultJsonFactory->create();
				return $result->setData(['url' => $url]);
			}

        } catch (\Exception $e) {
			$this->_logger->debug("TODOPAGO - FIRST STEP EXCEPTION");
            $order = $this->_getCheckoutSession()->getLastRealOrder();
            $method = $order->getPayment()->getMethod();
            $methodInstance = $this->_paymentHelper->getMethodInstance($method);
			$payment = $order->getPayment();

			if($methodInstance->getAmbiente() == "test") {                    
				$message = "Todo Pago (TEST): " . $e->getMessage();                    
			} else {                  
				$message = "Todo Pago: " . $e->getMessage();                  
			}

			$transaction = $this->transactionRepository->getByTransactionType(
				Transaction::TYPE_ORDER,
				$payment->getId(),
				$payment->getOrder()->getId()
			);

			if($transaction == null) {
				$orderTransactionId = $order->getId();
				$transaction = $this->transactionBuilder->setPayment($payment)
					->setOrder($order)
					->setTransactionId($order->getId())
					->build(Transaction::TYPE_ORDER);
			}

			$payment->addTransactionCommentsToOrder($transaction, $message);

			$statuses = $methodInstance->getOrderStatuses();
			$status = $statuses["rechazado"];
			$state = \Magento\Sales\Model\Order::STATE_CANCELED;
			$order->setState($state)->setStatus($status);
			$payment->setSkipOrderProcessing(true);
			$payment->setIsTransactionDenied(true);
			$order->cancel()->save();

            $this->messageManager->addException($e, __('Something went wrong, please try again later'));
            $this->_logger->critical($e);
            if($methodInstance->getRestoreCart())
	            $this->_getCheckoutSession()->restoreQuote();

            if($methodInstance->getCode() == "tpredirect") {
                $this->_redirect('checkout/cart');
	    } else {
		$url = $methodInstance->getErrorUrl();

		$result = $this->resultJsonFactory->create();
		return $result->setData(['url' => $url, 'error' => true]);
	    }
        }
    }
}
