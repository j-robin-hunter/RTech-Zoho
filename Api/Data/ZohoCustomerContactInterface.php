<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Api\Data;

interface ZohoCustomerContactInterface {

  /**
  * Get/Create Zoho contact from customer
  *
  * @param \Magento\Customer\Model\Data\Customer $customer
  * @return string
  */
  public function getContact($customer);

  /**
  * Update Zoho contact
  *
  * @param array $contact
  * @param \Magento\Customer\Model\Data\Customer $customer
  * @return array
  */
  public function updateContact($contact, $customer);

  /**
  * Update Zoho contact
  *
  * @param array $contact
  * @param \Magento\Customer\Api\Data\Address $billingAddress
  * @param \Magento\Customer\Api\Data\Address $shippingAddress
  * @param int $groupId
  * @return array
  */
  public function updateContactAddresses($contact, $billingAddress, $shippingAddress, $groupId);
  /**
  * Delete customer's Zoho contact
  *
  * @param \Magento\Customer\Model\Data\Customer $customer
  */
  public function deleteContact($customer);
}