<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Api\Data;

interface ZohoClientInterface {

  /**
  * Get all defined Zoho Books taxes
  *
  * @return array
  */
  public function getTaxes();

  /**
  * Get a Zoho Inventory item
  *
  * @param int $itemId
  * @return array
  */
  public function getItem($itemId);
}