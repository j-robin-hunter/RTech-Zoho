<?php
/**
 * Copyright © 2019 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use RTech\Zoho\Api\Data\ZohoSalesOrderManagementInterface;

class ZohoSalesOrderManagement extends AbstractModel implements IdentityInterface, ZohoSalesOrderManagementInterface {

  const CACHE_TAG = 'rtech_zoho_sales_order';

  protected $_cacheTag = self::CACHE_TAG;
  protected $_eventPrefix = self::CACHE_TAG;

  protected function _construct() {
    $this->_init(\RTech\Zoho\Model\ResourceModel\ZohoSalesOrderManagement::class);
  }

  /**
  * Return unique ID(s)
  *
  * @return string[]
  */
  public function getIdentities() {
    return [self::CACHE_TAG . '_' . $this->getId()];
  }

  /**
  * @inheritdoc
  */
  public function getId() {
    return (int)$this->getData(self::ORDER_ID);
  }

  /**
  * @inheritdoc
  */
  public function setId($orderId) {
    return $this->setData(self::ORDER_ID, $orderId);
  }

  /**
  * @inheritdoc
  */
  public function getZohoId() {
    return $this->getData(self::ZOHO_ID);
  }

  /**
  * @inheritdoc
  */
  public function setZohoId($zohoId) {
    return $this->setData(self::ZOHO_ID, $zohoId);
  }

  /**
  * @inheritdoc
  */
  public function getEstimateId() {
    return $this->getData(self::ESTIMATE_ID);
  }

  /**
  * @inheritdoc
  */
  public function setEstimateId($estimateId) {
    return $this->setData(self::ESTIMATE_ID, $estimateId);
  }

  /**
  * @inheritdoc
  */
  public function getSalesOrderId() {
    return $this->getData(self::SALES_ORDER_ID);
  }

  /**
  * @inheritdoc
  */
  public function setSalesOrderId($salesOrderId) {
    return $this->setData(self::SALES_ORDER_ID, $salesOrderId);
  }

  /**
  * @inheritdoc
  */
  public function getInvoiceId() {
    return $this->getData(self::INVOICE_ID);
  }

  /**
  * @inheritdoc
  */
  public function setInvoiceId($invoiceId) {
    return $this->setData(self::INVOICE_ID, $invoiceId);
  }
}