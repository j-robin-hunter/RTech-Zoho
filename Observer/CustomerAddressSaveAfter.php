<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Observer;

use Magento\Framework\Event\ObserverInterface;

class CustomerAddressSaveAfter implements ObserverInterface {

  protected $_zohoContact;

  public function __construct(
    \RTech\Zoho\Model\ZohoContact $zohoContact
  ) {
    $this->_zohoContact = $zohoContact;
  }

  public function execute(\Magento\Framework\Event\Observer $observer) {

    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/log_file_name.log');
    $this->_logger = new \Zend\Log\Logger();
    $this->_logger->addWriter($writer);
    $this->_logger->info('CustomerAddressSaveAfter');
    try {
      $this->_zohoContact->updateAddress($observer->getCustomerAddress());
    } catch (\Exception $e) {
      $this->_logger->info($e->getMessage());
    }
  }
}