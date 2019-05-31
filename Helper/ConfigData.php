<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/

namespace RTech\Zoho\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigData extends AbstractHelper
{
  const ZOHO_ORGANISATION_ID = 'zoho/organisation/id';
  const ZOHO_BOOKS_KEY  = 'zoho/books/api_key';
  const ZOHO_BOOKS_ENDPOINT = 'zoho/books/url';
  const ZOHO_INVENTORY_KEY  = 'zoho/inventory/api_key';
  const ZOHO_INVENTORY_ENDPOINT = 'zoho/inventory/url';

  public function __construct(
    ScopeConfigInterface $scopeConfig
  ) {
    $this->scopeConfig = $scopeConfig;
  }

  public function getZohoOrganistionId($storeId) {
    return (string)$this->scopeConfig->getValue(
      self::ZOHO_ORGANISATION_ID,
      ScopeInterface::SCOPE_STORE,
      $storeId
    );
  }

  public function getZohoBooksKey($storeId) {
    return (string)$this->scopeConfig->getValue(
      self::ZOHO_BOOKS_KEY,
      ScopeInterface::SCOPE_STORE,
      $storeId
    );
  }

  public function getZohoBooksEndpoint($storeId) {
    return (string)$this->scopeConfig->getValue(
      self::ZOHO_BOOKS_ENDPOINT,
      ScopeInterface::SCOPE_STORE,
      $storeId
    );
  }

  public function getZohoInventoryKey($storeId) {
    return (string)$this->scopeConfig->getValue(
      self::ZOHO_INVENTORY_KEY,
      ScopeInterface::SCOPE_STORE,
      $storeId
    );
  }

  public function getZohoInventoryEndpoint($storeId) {
    return (string)$this->scopeConfig->getValue(
      self::ZOHO_INVENTORY_ENDPOINT,
      ScopeInterface::SCOPE_STORE,
      $storeId
    );
  }
}