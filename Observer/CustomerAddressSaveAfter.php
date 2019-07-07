<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Observer;

use Magento\Framework\Event\ObserverInterface;

class CustomerAddressSaveAfter implements ObserverInterface {

  protected $_zohoContact;
  protected $_logger;

  public function __construct(
    \RTech\Zoho\Model\ZohoContact $zohoContact,
    \Psr\Log\LoggerInterface $logger
  ) {
    $this->_zohoContact = $zohoContact;
    $this->_logger = $logger;
  }

  public function execute(\Magento\Framework\Event\Observer $observer) {
    try {
      $this->_zohoContact->updateAddress($observer->getCustomerAddress());
    } catch (\Exception $e) {
      $this->_logger->error(__('Unable to update address: ' . $e->getMessage()));
    }
  }
}
