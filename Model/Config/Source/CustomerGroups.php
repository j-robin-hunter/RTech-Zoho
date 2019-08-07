<?php
/**
* Copyright © 2019 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Model\Config\Source;

class CustomerGroups implements \Magento\Framework\Option\ArrayInterface{
  protected $_customerGroup;

  public function __construct(
    \Magento\Customer\Model\ResourceModel\Group\Collection $customerGroup
  ) {
    $this->_customerGroup = $customerGroup;
  }

  public function toOptionArray() {
    return $this->_customerGroup->toOptionArray();
  }
}