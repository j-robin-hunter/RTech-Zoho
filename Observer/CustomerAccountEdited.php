<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Observer;

use Magento\Framework\Event\ObserverInterface;

class CustomerAccountEdited implements ObserverInterface {

    protected $_zohoContact;
    protected $_customerRepository;

  public function __construct(
    \RTech\Zoho\Model\ZohoContact $zohoContact,
    \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
  ) {
    $this->_zohoContact = $zohoContact;
    $this->_customerRepository = $customerRepository;
  }

  public function execute(\Magento\Framework\Event\Observer $observer) {
    $email = $observer->getEmail();
    $customer = $this->_customerRepository->get($email);
    $zohoContact = $this->_zohoContact->createContact($customer);
    $this->_zohoContact->updateContact($customer, $zohoContact);
  }
}