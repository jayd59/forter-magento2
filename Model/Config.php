<?php
/**
* Forter Payments For Magento 2
* https://www.Forter.com/
*
* @category Forter
* @package  Forter_Forter
* @author   Girit-Interactive (https://www.girit-tech.com/)
*/
namespace Forter\Forter\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
* Forter Forter config model.
*/
class Config
{
    const MODULE_NAME = 'Forter_Forter';

    /**
     * Scope config object.
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * Store manager object.
     *
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var EncryptorInterface
     */
    private $encryptor;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var UrlInterface
     */
    private $urlBuilder;
    /**
     * @method __construct
     * @param  ScopeConfigInterface  $scopeConfig
     * @param  StoreManagerInterface $storeManager
     * @param  EncryptorInterface    $encryptor
     * @param  LoggerInterface       $logger
     * @param  UrlInterface          $urlBuilder
     * @param  ModuleListInterface      $moduleList
     */
    public function __construct(
       ScopeConfigInterface $scopeConfig,
       StoreManagerInterface $storeManager,
       EncryptorInterface $encryptor,
       LoggerInterface $logger,
       ModuleListInterface $moduleList,
       UrlInterface $urlBuilder
   ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->encryptor = $encryptor;
        $this->logger = $logger;
        $this->moduleList = $moduleList;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Return config path.
     *
     * @return string
     */
    private function getConfigPath()
    {
        return sprintf('forter/');
    }

    /**
     * Return store manager.
     * @return StoreManagerInterface
     */
    public function getStoreManager()
    {
        return $this->storeManager;
    }

    /**
     * Return URL Builder
     * @return UrlInterface
     */
    public function getUrlBuilder()
    {
        return $this->urlBuilder;
    }

    /**
     * Return store id.
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * @return bool
     */
    public function getSiteId()
    {
        return $this->getConfigValue('settings/site_id');
    }

    /**
     * @return bool
     */
    public function getSecretKey()
    {
        $secretKey = $this->getConfigValue('settings/secret_key');
        $decryptSecretKey = $this->encryptor->decrypt($secretKey);
        return $decryptSecretKey;
    }

    /**
     * @return bool
     */
    public function getTimeOutSettings()
    {
        return $timeOutArray = [
       "base_connection_timeout" => $this->getConfigValue('connection_information/base_connection_timeout'),
       "base_request_timeout" => $this->getConfigValue('connection_information/base_request_timeout'),
       "max_connection_timeout" => $this->getConfigValue('connection_information/max_connection_timeout'),
       "max_request_timeout" => $this->getConfigValue('connection_information/max_request_timeout')
     ];
    }

    /**
     * @return bool
     */
    public function getApiVersion()
    {
        return '2.0';
    }

    /**
     * Return config field value.
     *
     * @param string $fieldKey Field key.
     *
     * @return mixed
     */
    private function getConfigValue($fieldKey)
    {
        return $this->scopeConfig->getValue(
           $this->getConfigPath() . $fieldKey,
           ScopeInterface::SCOPE_STORE,
           $this->getStoreId()
       );
    }

    /**
     * Return bool value depends of that if payment method sandbox mode
     * is enabled or not.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)$this->getConfigValue('settings/enabled');
    }

    /**
     * Return bool value depends of that if payment method sandbox mode
     * is enabled or not.
     *
     * @return bool
     */
    public function isSandboxMode()
    {
        return (bool)$this->getConfigValue('settings/sandbox_mode');
    }

    /**
     * Return bool value depends of that if payment method debug mode
     * is enabled or not.
     *
     * @return bool
     */
    public function isDebugEnabled()
    {
        return (bool)$this->getConfigValue('debug');
    }

    /**
     * @method getCurrentStore
     */
    public function getCurrentStore()
    {
        return $this->storeManager->getStore();
    }

    /**
     * @method log
     * @param  mixed   $message
     * @param  string  $type
     * @param  array   $data
     * @param  string  $prefix
     * @return $this
     */
    public function log($message, $type = "debug", $data = [], $prefix = '[Forter] ')
    {
        $this->logger->debug($prefix . json_encode($message), $data); //REMOVE LATER
        if ($type !== 'debug' || $this->isDebugEnabled()) {
            if (!isset($data['store_id'])) {
                $data['store_id'] = $this->getStoreId();
            }
            switch ($type) {
               case 'error':
                   $this->logger->error($prefix . json_encode($message), $data);
                   break;
               case 'info':
                   $this->logger->info($prefix . json_encode($message), $data);
                   break;
               case 'debug':
               default:
                   $this->logger->debug($prefix . json_encode($message), $data);
                   break;
           }
        }
        return $this;
    }

    public function getModuleVersion()
    {
        return $this->moduleList->getOne(self::MODULE_NAME)['setup_version'];
    }

    public function getPrePostDesicionMsg($type)
    {
        $result = $this->scopeConfig->getValue('forter/immediate_post_pre_decision/' . $type);
        switch ($type) {
         case 'pre_post_Select':
             return ($result == '1' ? 'Auth pre paymernt' : 'Auth post paymernt');
         case 'decline_pre':
             return ($result == '1' ? 'Redirect Success Page, Cancel the order, prevent email sending' : 'Send user back to Checkout page with error');
         case 'decline_post':
             return ($result == '1' ? 'Redirect Success Page, Cancel the order, prevent email sending' : 'Send user back to Checkout page with error');
         case 'capture_invoice':
             return ($result == '1' ? 'Capture (invoice) Cron' : 'Capture (invoice) Immediate');
         default:
             return $result;
     }
    }
}
