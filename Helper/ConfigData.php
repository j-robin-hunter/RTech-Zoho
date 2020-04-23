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
  const ZOHO_ORGANISATION_ID = 'zoho/organisation/id';
  const ZOHO_BOOKS_ENABLED  = 'zoho/books/enabled';
  const ZOHO_BOOKS_KEY  = 'zoho/books/api_key';
  const ZOHO_BOOKS_ENDPOINT = 'zoho/books/url';
  const MAGENTO_EU_VAT_GROUP = 'zoho/books/eu_vat_group';
  const ZOHO_INVENTORY_ENABLED  = 'zoho/inventory/enabled';
  const ZOHO_INVENTORY_KEY  = 'zoho/inventory/api_key';
  const ZOHO_INVENTORY_ENDPOINT = 'zoho/inventory/url';
  const ZOHO_SHIPPING_SKU = 'zoho/inventory/shipping_sku';
  const ZOHO_BOOKS_ESTIMATE_VALIDITY = 'zoho/estimate/validity';
  const ZOHO_BOOKS_ESTIMATE_TERMS = 'zoho/estimate/terms';
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

  public function getZohoOrganistionId($storeId) {
    return (string)$this->scopeConfig->getValue(
      self::ZOHO_ORGANISATION_ID,
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

  public function getMagentoEuVatGroupId($storeId) {$groupName = (string)$this->scopeConfig->getValue(
      self::MAGENTO_EU_VAT_GROUP,
      ScopeInterface::SCOPE_STORE,
      $storeId
    );

    $generalFilter[] = $this->filterBuilder
      ->setField('customer_group_code')
      ->setConditionType('eq')
      ->setValue($groupName)
      ->create();

    $searchCriteria = $this->searchCriteriaBuilder
      ->addFilters($generalFilter)
      ->create();

    if ($this->customerGroups === null) {
      $this->customerGroups = [];
      foreach ($this->groupRepository->getList($searchCriteria)->getItems() as $item) {
        $this->customerGroups[] = $item->getId();
      }
    }

    return count($this->customerGroups) > 0 ? $this->customerGroups[0] : null;
  }

  public function getZohoInventoryEnabled($storeId) {
    return (string)$this->scopeConfig->getValue(
      self::ZOHO_INVENTORY_ENABLED,
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

  public function getZohoShippingSku($storeId) {
    return (string)$this->scopeConfig->getValue(
      self::ZOHO_SHIPPING_SKU,
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

  public function getZohoInvoiceTerms($storeId) {
    return (string)$this->scopeConfig->getValue(
      self::ZOHO_BOOKS_INVOICE_TERMS,
      ScopeInterface::SCOPE_STORE,
      $storeId
    );
  }
}