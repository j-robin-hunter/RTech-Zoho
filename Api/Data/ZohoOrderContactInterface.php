<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Api\Data;

interface ZohoOrderContactInterface {

  /**
  * Get/Create Zoho contact from order
  *
  * @param array $order
  * @return string
  */
  public function getContactForOrder($order);

  /**
  * Update Zoho contact
  *
  * @param array $contact
  * @param array $order
  * @return array
  */
  public function updateOrderContact($contact, $order);
}