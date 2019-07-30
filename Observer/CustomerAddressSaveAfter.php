<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Observer;

use Magento\Framework\Event\ObserverInterface;

class CustomerAddressSaveAfter implements ObserverInterface {

  protected $_zohoCustomerContact;
  protected $_customerRepository;
  protected $_customerFactory;
  protected $_addressRepository;
  protected $_logger;

  public function __construct(
    \RTech\Zoho\Model\ZohoCustomerContact $zohoCustomerContact,
    \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
    \Magento\Customer\Model\CustomerFactory $customerFactory,
    \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
    \Psr\Log\LoggerInterface $logger
  ) {
    $this->_zohoCustomerContact = $zohoCustomerContact;
    $this->_customerRepository = $customerRepository;
    $this->_customerFactory = $customerFactory;
    $this->_addressRepository = $addressRepository;
    $this->_logger = $logger;
  }

  public function execute(\Magento\Framework\Event\Observer $observer) {
    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/log_file_name.log');
    $this->_logger = new \Zend\Log\Logger();
    $this->_logger->addWriter($writer);
    $this->_logger->info('CustomerAddressAfter');

    $address = $observer->getCustomerAddress();
    $customer = $this->_customerFactory->create()->load($address->getId());
    $this->_logger->info(get_class($address));
    $this->_logger->info(get_class($customer));
    $this->_logger->info('billing id ' . $address->getDefaultBilling());
    $this->_logger->info('shipping id ' . $address->getDefaultShipping());

    $customer = $this->_customerRepository->getById($address->getCustomerId());
    $this->_logger->info(get_class($customer));
    $this->_logger->info('existing billing: ' . $customer->getDefaultBilling());
    $this->_logger->info('existing shipping: ' . $customer->getDefaultShipping());

    // If we have a billing address already and this function is not trying to change the
    // billing address then retrieve the existing billing address. Otherwise either
    // use the address passed to this function if it is a billing address or set the
    // billing address to null
    if ($customer->getDefaultBilling() && ! $address->getDefaultBilling()) {
      $this->_logger->info('use existing billing');
      $billingAddress = $this->_addressRepository->getById($customer->getDefaultBilling());
    } else {
      $this->_logger->info('use billing addressed passed or null');
      $billingAddress = $address->getDefaultBilling()?$address->getDataModel():null;
    }

    // If we have a shipping address already and this function is not trying to change the
    // shipping address then retrieve the existing shipping address. Otherwise either
    // use the address passed to this function if it is a shipping address or set the
    // shipping address to null
    if ($customer->getDefaultShipping() && ! $address->getDefaultShipping()) {
      $this->_logger->info('use existing shipping');
      $shippingAddress = $this->_addressRepository->getById($customer->getDefaultShipping());
    } else {
      $this->_logger->info('use shipping addressed passed or null');
      $shippingAddress = $address->getDefaultShipping()?$address->getDataModel():null;
    }

    if ($billingAddress || $shippingAddress) {
      //$customer = $this->_customerRepository->getById($address->getCustomerId());
      try {
        $zohoContact = $this->_zohoCustomerContact->getContact($customer);
        $zohoContact = $this->_zohoCustomerContact->updateContactAddresses($zohoContact, $billingAddress, $shippingAddress, $customer->getGroupId());
      } catch (\Exception $e) {
        $this->_logger->error(__('Unable to update address: ' . $e->getMessage()));
      }
    }
  }
}
