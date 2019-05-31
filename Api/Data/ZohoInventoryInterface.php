<?php
/**
 * Copyright © 2019 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Api\Data;

interface ZohoInventoryInterface {
  const PRODUCT_ID = 'product_id';
  const PRODUCT_NAME = 'product_name';
  const ZOHO_ID = 'zoho_id';
  const ZOHO_TYPE = 'zoho_type';

  /**
  * Get product id
  *
  * @return int|null
  */
  public function getId();

  /**
  * Set product id
  *
  * @param int $productId
  * @return $this
  */
  public function setId($productId);

  /**
  * Get product name
  *
  * @return string|null
  */
  public function getProductName();

  /**
  * Set product name
  *
  * @param string $productName
  * @return $this
  */
  public function setProductName($productName);

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

  /**
  * Get Zoho type
  *
  * @return string|null
  */
  public function getZohoType();

  /**
  * Set Zoho type
  *
  * @param string $zohoType
  * @return $this
  */
  public function setZohoType($zohoType);
}