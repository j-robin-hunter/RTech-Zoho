<?php
/**
 * Copyright © 2019 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Api\Data;

interface ZohoAddressInterface {
  const CUSTOMER_ADDRESS_ID = 'customer_address_id';
  const CUSTOMER_ID = 'customer_id';
  const BILLING = 'billing';
  const SHIPPING = 'shipping';
  const ZOHO_ADDRESS_ID = 'zoho_address_id';

  /**
  * Get customer address id
  *
  * @return int|null
  */
  public function getId();

  /**
  * Set customer address id
  *
  * @param int $customerAddressId
  * @return $this
  */
  public function setId($customerAddressId);

  /**
  * Get customer id
  *
  * @return string|null
  */
  public function getCustomerId();

  /**
  * Set customer id
  *
  * @param int $customerId
  * @return $this
  */
  public function setCustomerId($customerId);

  /**
  * Get billing address type
  *
  * @return bool|null
  */
  public function getBilling();

  /**
  * Set billing address type
  *
  * @param bool $billing
  * @return $this
  */
  public function setBilling($billing);

  /**
  * Get shipping address type
  *
  * @return bool|null
  */
  public function getShipping();

  /**
  * Set shipping address type
  *
  * @param bool $shipping
  * @return $this
  */
  public function setShipping($shipping);

  /**
  * Get Zoho address id
  *
  * @return string|null
  */
  public function getZohoAddressId();

  /**
  * Set Zoho address id
  *
  * @param string $zohoAddressId
  * @return $this
  */
  public function setZohoAddressId($zohoAddressId);
}