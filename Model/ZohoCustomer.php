<?php
/**
 * Copyright © 2019 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use RTech\Zoho\Api\Data\ZohoCustomerInterface;

class ZohoCustomer extends AbstractModel implements IdentityInterface, ZohoCustomerInterface {

  const CACHE_TAG = 'rtech_zoho_customer';

  protected $_cacheTag = self::CACHE_TAG;
  protected $_eventPrefix = self::CACHE_TAG;

  protected function _construct() {
    $this->_init(\RTech\Zoho\Model\ResourceModel\ZohoCustomer::class);
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
    return (int)$this->getData(self::CUSTOMER_ID);
  }

  /**
  * @inheritdoc
  */
  public function setId($customerId) {
    return $this->setData(self::CUSTOMER_ID, $customerId);
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
}