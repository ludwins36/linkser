<?php

namespace Vexsoluciones\Linkser\Observer;

use Exception;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Vexsoluciones\Linkser\Helper\Config;

/**
 * Class SalesPaymentImportData
 *
 * @package Vexsoluciones\Linkser\Observer
 */
class SalesPaymentImportData implements ObserverInterface
{
    /**
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $payment = $observer->getEvent()->getPayment();
        $input = $observer->getEvent()->getInput();
        if ($input->getMethod() == Config::PAYMENT_METHOD) {
            try {
                $payment->addData($input->getAdditionalData())->save();
            } catch (Exception $e) {
                throw new CouldNotSaveException($e->getMessage());
            }
        }
    }
}
