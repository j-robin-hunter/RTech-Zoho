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
    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/log_file_name.log');
    $this->_logger = new \Zend\Log\Logger();
    $this->_logger->addWriter($writer);
    $this->_logger->info('CustomerAddressDeleteAfter');

    $address = $observer->getCustomerAddress()->getDataModel();
    $this->_logger->info(get_class($address));
    $this->_logger->info($address->getId());
    $this->_logger->info('billing? ' . $address->isDefaultBilling());
    $this->_logger->info('shipping? ' . $address->isDefaultShipping());

    $billingAddress = $address->isDefaultBilling()?$address:null;
    $shippingddress = $address->isDefaultShipping()?$address:null;

    if ($billingAddress || $shippingddress) {
      $customer = $this->_customerRepository->getById($address->getCustomerId());
      $this->_logger->info(get_class($customer));

      try {
        $zohoContact = $this->_zohoCustomerContact->getContact($customer);
        $this->_logger->info($customer->getGroupId());
        $zohoContact = $this->_zohoCustomerContact->updateContactAddresses($zohoContact, $billingAddress, $shippingddress, $customer->getGroupId());
      } catch (\Exception $e) {
        $this->_logger->error(__('Unable to update address: ' . $e->getMessage()));
      }
    } else {
      $this->_logger->info('additional address');
    }
  }
}