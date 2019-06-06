<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Api\Data;

interface ZohoContactInterface {

  /**
  * Add Zoho contact
  *
  * @param array $customer
  * @return array
  */
  public function createContact($customer);

  /**
  * Update Zoho contact
  *
  * @param array $customer
  * @param array $contact
  * @return array
  */
  public function updateContact($customer, $contact);

  /**
  * Update Zoho contact address
  *
  * @param array $address
  * @return array
  */
  public function updateAddress($address);

  /**
  * Delete Zoho contact
  *
  * @param array $customer
  * @return array
  */
  public function deleteContact($customer);
}