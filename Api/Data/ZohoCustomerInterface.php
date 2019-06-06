<?php
/**
 * Copyright © 2019 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Api\Data;

interface ZohoCustomerInterface {
  const CUSTOMER_ID = 'customer_id';
  const ZOHO_ID = 'zoho_id';

  /**
  * Get customer id
  *
  * @return int|null
  */
  public function getId();

  /**
  * Set customer id
  *
  * @param int $customerId
  * @return $this
  */
  public function setId($customerId);

  /**
  * Get Zoho id
  *
  * @return string|null
  */
  public function getZohoId();

  /**
  * Set Zoho id
  *
  * @param string $zohoId
  * @return $this
  */
  public function setZohoId($zohoId);
}