<?php
/**
 * Copyright © 2019 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use RTech\Zoho\Api\Data\ZohoAddressInterface;

class ZohoAddress extends AbstractModel implements IdentityInterface, ZohoAddressInterface {

  const CACHE_TAG = 'rtech_zoho_address';

  protected $_cacheTag = self::CACHE_TAG;
  protected $_eventPrefix = self::CACHE_TAG;

  protected function _construct() {
    $this->_init(\RTech\Zoho\Model\ResourceModel\ZohoAddress::class);
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
    return (int)$this->getData(self::CUSTOMER_ADDRESS_ID);
  }

  /**
  * @inheritdoc
  */
  public function setId($entityId) {
    return $this->setData(self::CUSTOMER_ADDRESS_ID, $entityId);
  }

  /**
  * @inheritdoc
  */
  public function getCustomerId() {
    return $this->getData(self::CUSTOMER_ID);
  }

  /**
  * @inheritdoc
  */
  public function setCustomerId($customerId) {
    return $this->setData(self::CUSTOMER_ID, $customerId);
  }

  /**
  * @inheritdoc
  */
  public function getBilling() {
    return $this->getData(self::BILLING);
  }

  /**
  * @inheritdoc
  */
  public function setBilling($billing) {
    return $this->setData(self::BILLING, $billing);
  }

  /**
  * @inheritdoc
  */
  public function getShipping() {
    return $this->getData(self::SHIPPING);
  }

  /**
  * @inheritdoc
  */
  public function setShipping($shipping) {
    return $this->setData(self::SHIPPING, $shipping);
  }

  /**
  * @inheritdoc
  */
  public function getZohoAddressId() {
    return $this->getData(self::ZOHO_ADDRESS_ID);
  }

  /**
  * @inheritdoc
  */
  public function setZohoAddressId($zohoAddressId) {
    return $this->setData(self::ZOHO_ADDRESS_ID, $zohoAddressId);
  }
}