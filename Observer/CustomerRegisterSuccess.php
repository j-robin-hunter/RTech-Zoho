<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Observer;

use Magento\Framework\Event\ObserverInterface;

class CustomerRegisterSuccess implements ObserverInterface {
  protected $_zohoCustomerContact;

  public function __construct(
    \RTech\Zoho\Model\ZohoCustomerContact $zohoCustomerContact
  ) {
    $this->_zohoCustomerContact = $zohoCustomerContact;
  }

  public function execute(\Magento\Framework\Event\Observer $observer) {
    $this->_zohoCustomerContact->getContact($observer->getCustomer());
  }
}