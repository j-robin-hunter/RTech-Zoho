<?php
/**
* Copyright © 2019 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Model\ResourceModel\ZohoAddress;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection {
  /**
* Resource initialization
*
* @return void
*/
  protected function _construct() {
    $this->_init(\RTech\Zoho\Model\ZohoAddress::class, \RTech\Zoho\Model\ResourceModel\ZohoAddress::class);
  }
}