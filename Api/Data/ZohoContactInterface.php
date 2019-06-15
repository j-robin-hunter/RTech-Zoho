<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Api\Data;

interface ZohoContactInterface {

  /**
  * Get/Create Zoho contact from order
  *
  * @param array $order
  * @return string
  */
  public function getContactId($order);

  /**
  * Update Zoho contact
  *
  * @param array $contact
  * @param array $order
  * @return array
  */
  public function updateContact($contact, $order);

  /**
  * Delete customer's Zoho contact
  *
  * @param array $contact
  * @return array
  */
  public function deleteContact($customer);
}