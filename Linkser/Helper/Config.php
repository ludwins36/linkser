<?php

namespace Vexsoluciones\Linkser\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Config
 *
 * @package Vexsoluciones\Linkser\Helper
 */
class Config extends AbstractHelper
{
    /**
     * @var string
     */
    const PAYMENT_METHOD = 'linkser_creditcard';

    /**
     * @var string
     */
    const MERCHANT_ID_CONFIG_PATH = 'payment/linkser_creditcard/merchant_id';

    /**
     * @var string
     */
    const SECRET_KEY_CONFIG_PATH = 'payment/linkser_creditcard/secret_key';

    /**
     * @var string
     */
    const VERSION_CONFIG_PATH = 'payment/linkser_creditcard/version';

    /**
     * @var string
     */
    const PAYMENT_URL_PATH = 'payment/linkser_creditcard/payment_url';

    /**
     * @var string
     */
    const TEST_MODE_PATH = 'payment/linkser_creditcard/testmode';

    /**
     * @var string
     */
    const TEST_PAYMENT_URL_PATH = 'payment/linkser_creditcard/test_payment_url';

    /**
     * @var string
     */
    const CURRENCY_NO_URL_PATH = 'payment/linkser_creditcard/currency_no';

    /**
     * @var string
     */
    const DEBUG_URL_PATH = 'payment/linkser_creditcard/debug';

    /**
     * @var string
     */
    const DESCRIPTION_PATH = 'payment/linkser_creditcard/description';

    /**
     * @var string
     */
    const CHECKAOUT_FAILURE_PAGE_PATH = 'payment/linkser_creditcard/checkout_failure_page';

    /**
     * @var string
     */
    const USE_NAME_ON_CARD_PATH = 'payment/linkser_creditcard/use_card_name';

    /**
     * @return string
     */
    public function getMerchantId()
    {
        return $this->scopeConfig->getValue(self::MERCHANT_ID_CONFIG_PATH, ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return string
     */
    public function getSecretKey()
    {
        return $this->scopeConfig->getValue(self::SECRET_KEY_CONFIG_PATH, ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->scopeConfig->getValue(self::VERSION_CONFIG_PATH, ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return string
     */
    public function getPaymentUrl()
    {
        if ($this->getTestMode()) {
            return $this->scopeConfig->getValue(self::TEST_PAYMENT_URL_PATH, ScopeInterface::SCOPE_WEBSITE);
        }
        return $this->scopeConfig->getValue(self::PAYMENT_URL_PATH, ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return string
     */
    public function getTestMode()
    {
        return $this->scopeConfig->getValue(self::TEST_MODE_PATH, ScopeInterface::SCOPE_WEBSITE);

    }

    /**
     * @return string
     */
    public function getCurrencyNo()
    {
        return $this->scopeConfig->getValue(self::CURRENCY_NO_URL_PATH, ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return string
     */
    public function isAllowDebug()
    {
        return $this->scopeConfig->getValue(self::DEBUG_URL_PATH, ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->scopeConfig->getValue(self::DESCRIPTION_PATH, ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return string
     */
    public function getCheckoutFailurePage()
    {
        return $this->scopeConfig->getValue(self::CHECKAOUT_FAILURE_PAGE_PATH);
    }

    /**
     * @return string
     */
    public function isUseNameOnCard()
    {
        return $this->scopeConfig->getValue(self::USE_NAME_ON_CARD_PATH, ScopeInterface::SCOPE_WEBSITE);
    }
}
