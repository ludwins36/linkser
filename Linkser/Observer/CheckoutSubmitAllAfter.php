<?php

namespace Vexsoluciones\Linkser\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vexsoluciones\Linkser\Gateway\Response\Handler\Request\Transaction;
use Vexsoluciones\Linkser\Helper\Config;

/**
 * Class CheckoutSubmitAllAfter
 *
 * @package Vexsoluciones\Linkser\Observer
 */
class CheckoutSubmitAllAfter implements ObserverInterface
{
    /**
     * @var \Vexsoluciones\Linkser\Gateway\Response\Handler\Request\Transaction
     */
    private $transaction;

    /**
     * CheckoutSubmitAllAfter constructor.
     *
     * @param \Vexsoluciones\Linkser\Gateway\Response\Handler\Request\Transaction $transaction
     */
    public function __construct(
        Transaction $transaction
    ) {
        $this->transaction = $transaction;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if ($order->getPayment()->getMethod() == Config::PAYMENT_METHOD) {
            $quote = $observer->getEvent()->getQuote();
            $encCardData = $quote->getPayment()->getData('additional_data');

            $this->transaction->handle($order, $encCardData);
        }
    }
}
