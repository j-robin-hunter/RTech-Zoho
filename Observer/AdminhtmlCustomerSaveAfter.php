<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Observer;

use Magento\Framework\Event\ObserverInterface;

class AdminhtmlCustomerSaveAfter implements ObserverInterface {

  protected $_zohoContact;
  protected $_messageManager;

  public function __construct(
    \RTech\Zoho\Model\ZohoContact $zohoContact,
    \Magento\Framework\Message\ManagerInterface $messageManager
  ) {
    $this->_zohoContact = $zohoContact;
    $this->_messageManager = $messageManager;
  }

  public function execute(\Magento\Framework\Event\Observer $observer) {
    $customer = $observer->getCustomer();
    try {
      $zohoContact = $this->_zohoContact->createContact($observer->getCustomer());
      $this->_zohoContact->updateContact($customer, $zohoContact);
    } catch (\Exception $e) {
      $this->_messageManager->addNotice($e->getMessage());
      throw $e;
    }
  }
}
