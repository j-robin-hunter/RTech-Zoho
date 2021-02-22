<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Api\Data;

interface ZohoCrmClientInterface {

  /**
  * Add an item to Zoho Inventory
  *
  * @param string $module
  * @param string $id
  * @return array
  */
  public function getRecord($module, $id);
}