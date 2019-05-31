<?php
/**
 * Copyright © 2019 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use RTech\Zoho\Api\Data\ZohoInventoryInterface;

class ZohoInventory extends AbstractModel implements IdentityInterface, ZohoInventoryInterface {

  const CACHE_TAG = 'rtech_zoho_inventory';

  protected $_cacheTag = self::CACHE_TAG;
  protected $_eventPrefix = self::CACHE_TAG;

  protected function _construct() {
    $this->_init(\RTech\Zoho\Model\ResourceModel\ZohoInventory::class);
  }

  /**
  * Return unique ID(s)
  *
  * @return string[]
  */
  public function getIdentities() {
    return [self::CACHE_TAG . '_' . $this->getProductId()];
  }

  /**
  * @inheritdoc
  */
  public function getId() {
    return (int)$this->getData(self::PRODUCT_ID);
  }

  /**
  * @inheritdoc
  */
  public function setId($productId) {
    return $this->setData(self::PRODUCT_ID, $productId);
  }

  /**
  * @inheritdoc
  */
  public function getProductName() {
    return $this->getData(self::PRODUCT_NAME);
  }

  /**
  * @inheritdoc
  */
  public function setProductName($productName) {
    return $this->setData(self::PRODUCT_NAME, $productName);
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
  public function getZohoType() {
    return $this->getData(self::ZOHO_TYPE);
  }

  /**
  * @inheritdoc
  */
  public function setZohoType($zohoType) {
    return $this->setData(self::ZOHO_TYPE, $zohoType);
  }
}