<?php
/**
* Copyright © 2019 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/

namespace RTech\Zoho\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class ConfigData extends AbstractHelper
{
  const ZOHO_AUTH_URL = 'zoho/organisation/authurl';
  const ZOHO_REFRESH_TOKEN = 'zoho/organisation/refreshtoken';
  const ZOHO_CLIENT_ID = 'zoho/organisation/clientid';
  const ZOHO_CLIENT_SECRET = 'zoho/organisation/clientsecret';
  const ZOHO_BOOKS_ENABLED  = 'zoho/books/enabled';
  const ZOHO_BOOKS_ENDPOINT = 'zoho/books/url';
  const MAGENTO_EU_VAT_GROUP = 'zoho/books/eu_vat_group';
  const ZOHO_INVENTORY_ENABLED  = 'zoho/inventory/enabled';
  const ZOHO_INVENTORY_ENDPOINT = 'zoho/inventory/url';
  const ZOHO_SHIPPING_SKU = 'zoho/inventory/shipping_sku';
  const ZOHO_CRM_ENDPOINT = 'zoho/crm/url';
  const ZOHO_BOOKS_ESTIMATE_VALIDITY = 'zoho/estimate/validity';
  const ZOHO_BOOKS_ESTIMATE_TERMS = 'zoho/estimate/terms';
  const ZOHO_BOOKS_ESTIMATE_EMAIL = 'zoho/estimate/email';
  const ZOHO_BOOKS_INVOICE_TERMS = 'zoho/invoice/terms';

  protected $scopeConfig;
  protected $groupRepository;
  protected $searchCriteriaBuilder;
  protected $filterBuilder;
  protected $customerGroups;

  public function __construct(
    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
    \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
    \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
    \Magento\Framework\Api\FilterBuilder $filterBuilder
  ) {
    $this->scopeConfig = $scopeConfig;
    $this->groupRepository = $groupRepository;
    $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    $this->filterBuilder = $filterBuilder;
  }
  
  public function getZohoAuthUrl($storeId) {
    return (string)$this->scopeConfig->getValue(
      self::ZOHO_AUTH_URL,
      ScopeInterface::SCOPE_STORE,
      $storeId
    );
  }

  public function getZohoRefreshToken($storeId) {
    return (string)$this->scopeConfig->getValue(
      self::ZOHO_REFRESH_TOKEN,
      ScopeInterface::SCOPE_STORE,
      $storeId
    );
  }
  
  public function getZohoClientId($storeId) {
    return (string)$this->scopeConfig->getValue(
      self::ZOHO_CLIENT_ID,
      ScopeInterface::SCOPE_STORE,
      $storeId
    );
  }
  
  public function getZohoClientSecret($storeId) {
    return (string)$this->scopeConfig->getValue(
      self::ZOHO_CLIENT_SECRET,
      ScopeInterface::SCOPE_STORE,
      $storeId
    );
  }

  public function getZohoBooksEnabled($storeId) {
    return (string)$this->scopeConfig->getValue(
      self::ZOHO_BOOKS_ENABLED,
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

  public function getZohoInventoryEnabled($storeId) {
    return (string)$this->scopeConfig->getValue(
      self::ZOHO_INVENTORY_ENABLED,
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

  public function getZohoShippingSku($storeId) {
    return (string)$this->scopeConfig->getValue(
      self::ZOHO_SHIPPING_SKU,
      ScopeInterface::SCOPE_STORE,
      $storeId
    );
  }

  public function getZohoCrmEndpoint($storeId) {
    return (string)$this->scopeConfig->getValue(
      self::ZOHO_CRM_ENDPOINT,
      ScopeInterface::SCOPE_STORE,
      $storeId
    );
  }

  public function getZohoEstimateValidity($storeId) {
    return (int)$this->scopeConfig->getValue(
      self::ZOHO_BOOKS_ESTIMATE_VALIDITY,
      ScopeInterface::SCOPE_STORE,
      $storeId
    );
  }

  public function getZohoEstimateTerms($storeId) {
    return (string)$this->scopeConfig->getValue(
      self::ZOHO_BOOKS_ESTIMATE_TERMS,
      ScopeInterface::SCOPE_STORE,
      $storeId
    );
  }

  public function getZohoEstimateEmail($storeId) {
    return (int)$this->scopeConfig->getValue(
      self::ZOHO_BOOKS_ESTIMATE_EMAIL,
      ScopeInterface::SCOPE_STORE,
      $storeId
    );
  }

  public function getZohoInvoiceTerms($storeId) {
    return (string)$this->scopeConfig->getValue(
      self::ZOHO_BOOKS_INVOICE_TERMS,
      ScopeInterface::SCOPE_STORE,
      $storeId
    );
  }
}