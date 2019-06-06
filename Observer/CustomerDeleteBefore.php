<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Observer;

use Magento\Framework\Event\ObserverInterface;

class CustomerDeleteBefore implements ObserverInterface {

  protected $_zohoContact;

  public function __construct(
    \RTech\Zoho\Model\ZohoContact $zohoContact
  ) {
    $this->_zohoContact = $zohoContact;
  }


  public function execute(\Magento\Framework\Event\Observer $observer) {
    $this->_zohoContact->deleteContact($observer->getCustomer());
  }
}