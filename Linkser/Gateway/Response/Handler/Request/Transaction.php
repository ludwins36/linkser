<?php

namespace Vexsoluciones\Linkser\Gateway\Response\Handler\Request;

use Vexsoluciones\Linkser\Model\Curl;
use Vexsoluciones\Linkser\Helper\Config;
use Psr\Log\LoggerInterface;

/**
 * Class Transaction
 *
 * @package Vexsoluciones\Linkser\Gateway\Response\Handler\Request
 */
class Transaction
{
    /**
     * Length required by linkser
     *
     * @var string
     */
    const MAX_AMOUNT_LENGTH = 12;

    /**
     * @var \Vexsoluciones\Linkser\Helper\Config
     */
    protected $config;

    /**
     * @var \Vexsoluciones\Linkser\Model\Curl
     */
    protected $curl;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Transaction constructor.
     *
     * @param \Vexsoluciones\Linkser\Helper\Config $config
     * @param \Vexsoluciones\Linkser\Model\Curl $curl
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Config $config,
        Curl $curl,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->curl = $curl;
        $this->logger = $logger;
    }

    /**
     * @param $order
     * @param $encCardData
     * @param $cardName
     *
     * @return mixed
     */
    public function handle($order, $encCardData, $cardName = null)
    {
        $url = $this->config->getPaymentUrl();
        $version = $this->config->getVersion();
        $merchanId = $this->config->getMerchantId();
        $secretKey = $this->config->getSecretKey();
        $desc = $this->config->getDescription();
        $uniqueTransactionCode = $order->getIncrementId();
        $currencyCode = $this->config->getCurrencyNo();
        $amt  = $this->getAmount($order->getGrandTotal());
        $billingAddress = $order->getBillingAddress();
        $panCountry = $billingAddress->getCountryId();
        $cardholderName = $cardName ?? sprintf('%s %s', $billingAddress->getFirstname(), $billingAddress->getLastname());

        $paymentRequest = $this->getPaymentRequest(
            $merchanId,
            $uniqueTransactionCode,
            $desc,
            $amt,
            $currencyCode,
            $panCountry,
            $cardholderName,
            $encCardData,
            $secretKey,
            $version
        );

        if ($this->config->isAllowDebug()) {
            $this->logger->info(__('Request linkser for order id %1', $order->getId()));
            $this->logger->info($url);
            $this->logger->info($paymentRequest);
        }

        $this->curl->post($url, ['paymentRequest' => $paymentRequest]);
        $response = $this->curl->getBody();
        return $response;
    }

    /**
     * Fix require length on linkser gateway
     *
     * @param $grandTotal
     *
     * @return string
     */
    private function getAmount($grandTotal)
    {
        $grandTotal = number_format(round($grandTotal, 2), 2, '', '');
        $strLength = strlen($grandTotal);
        $length = self::MAX_AMOUNT_LENGTH - $strLength;
        return sprintf("%0" .$length. "s%s", '0', $grandTotal);
    }

    /**
     * Generate Paymnet Request
     * @param $merchantID
     * @param $uniqueTransactionCode
     * @param $desc
     * @param $amt
     * @param $currencyCode
     * @param $panCountry
     * @param $cardholderName
     * @param $encCardData
     * @param $secretKey
     * @param $version
     *
     * @return string
     */
    private function getPaymentRequest(
        $merchantID,
        $uniqueTransactionCode,
        $desc,
        $amt,
        $currencyCode,
        $panCountry,
        $cardholderName,
        $encCardData,
        $secretKey,
        $version
    ) {
        $xml = "<PaymentRequest>
		<merchantID>$merchantID</merchantID>
		<uniqueTransactionCode>$uniqueTransactionCode</uniqueTransactionCode>
		<desc>$desc</desc>
		<amt>$amt</amt>
		<currencyCode>$currencyCode</currencyCode>  
		<panCountry>$panCountry</panCountry> 
		<cardholderName>$cardholderName</cardholderName>
		<encCardData>$encCardData</encCardData>
		</PaymentRequest>";
        $paymentPayload = base64_encode($xml);
        $signature = strtoupper(hash_hmac('sha256', $paymentPayload, $secretKey, false));
        $payloadXML = "<PaymentRequest>
           <version>$version</version>
           <payload>$paymentPayload</payload>
           <signature>$signature</signature>
           </PaymentRequest>";
        return base64_encode($payloadXML);
    }
}