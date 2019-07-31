<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Observer;

use Magento\Framework\Event\ObserverInterface;

class CustomerAddressDeleteAfter implements ObserverInterface {

  protected $_zohoCustomerContact;
  protected $_customerRepository;
  protected $_logger;

  public function __construct(
    \RTech\Zoho\Model\ZohoCustomerContact $zohoCustomerContact,
    \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
    \Psr\Log\LoggerInterface $logger
  ) {
    $this->_zohoCustomerContact = $zohoCustomerContact;
    $this->_customerRepository = $customerRepository;
    $this->_logger = $logger;
  }

  public function execute(\Magento\Framework\Event\Observer $observer) {
    $address = $observer->getCustomerAddress()->getDataModel();

    $billingAddress = $address->isDefaultBilling()?$address:null;
    $shippingddress = $address->isDefaultShipping()?$address:null;

    if ($billingAddress || $shippingddress) {
      $customer = $this->_customerRepository->getById($address->getCustomerId());

      try {
        $zohoContact = $this->_zohoCustomerContact->getContact($customer);
        $zohoContact = $this->_zohoCustomerContact->updateContactAddresses($zohoContact, $billingAddress, $shippingddress, $customer->getGroupId());
      } catch (\Exception $e) {
        $this->_logger->error(__('Unable to update address: ' . $e->getMessage()));
      }
    } else {
    }
  }
}