<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Observer;

use Magento\Framework\Event\ObserverInterface;

class AdminhtmlCustomerSaveAfter implements ObserverInterface {

  protected $_zohoCustomerContact;
  protected $_messageManager;

  public function __construct(
    \RTech\Zoho\Model\ZohoCustomerContact $zohoCustomerContact,
    \Magento\Framework\Message\ManagerInterface $messageManager
  ) {
    $this->_zohoCustomerContact = $zohoCustomerContact;
    $this->_messageManager = $messageManager;
  }

  public function execute(\Magento\Framework\Event\Observer $observer) {
    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/log_file_name.log');
    $this->_logger = new \Zend\Log\Logger();
    $this->_logger->addWriter($writer);
    $this->_logger->info('AdminhtmlCustomerSaveAfter');
    $customer = $observer->getCustomer();
    $this->_logger->info(get_class($customer));
    try {
      $this->_zohoCustomerContact->getContact($customer);
    $this->_logger->info('got here');
    } catch (\Exception $e) {
      $this->_messageManager->addNotice($e->getMessage());
      throw $e;
    }
  }
}
