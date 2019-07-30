<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Observer;

use Magento\Framework\Event\ObserverInterface;

class CustomerAccountEdited implements ObserverInterface {

    protected $_zohoCustomerContact;
    protected $_customerRepository;

  public function __construct(
    \RTech\Zoho\Model\ZohoCustomerContact $zohoCustomerContact,
    \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
  ) {
    $this->_zohoCustomerContact = $zohoCustomerContact;
    $this->_customerRepository = $customerRepository;
  }

  public function execute(\Magento\Framework\Event\Observer $observer) {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/log_file_name.log');
    $this->_logger = new \Zend\Log\Logger();
    $this->_logger->addWriter($writer);
    $this->_logger->info('execute');

    $email = $observer->getEmail();
    $customer = $this->_customerRepository->get($email);
    $zohoContact = $this->_zohoCustomerContact->getContact($customer);
    $this->_zohoContact->updateContact($customer, $zohoContact);
  }
}