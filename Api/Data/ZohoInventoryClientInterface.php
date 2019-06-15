<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Api\Data;

interface ZohoInventoryClientInterface {

  /**
  * Add an item to Zoho Inventory
  *
  * @param array $item
  * @return array
  */
  public function itemAdd($item);

  /**
  * Add an itemgroup to Zoho Inventory
  *
  * @param array $itemGroup
  * @return array
  */
  public function itemGroupAdd($itemGroup);

  /**
  * Add an image to a Zoho Inventory item
  *
  * @param int $itemId
  * @param string $imageFile
  * @return array
  */
  public function imageAdd($itemId, $imageFile);

  /**
  * Add a package to Zoho Inventory
  *
  * @param string $salesOrderId
  * @param array $package
  * @return array
  */
  public function packageAdd($salesOrderId, $package);

    /**
  * Add a shipment to Zoho Inventory
  *
  * @param array $package
  * @param array $shipment
  * @return array
  */
  public function shipmentAdd($package, $shipment);

  /**
  * Get a Zoho Inventory item by name
  *
  * @param string $itemName
  * @return array
  */
  public function getItemByName($itemName);

  /**
  * Get a Zoho Inventory itemgroup
  *
  * @param int $groupId
  * @return array
  */
  public function getItemGroup($groupId);

  /**
  * Get a Zoho Inventory itemgroup by name
  *
  * @param string $groupName
  * @return array
  */
  public function getItemGroupByName($groupName);

  /**
  * Get a Zoho Inventory sales order
  *
  * @param string $salesOrderId
  * @return array
  */
  public function getSalesOrder($salesOrderId);

  /**
  * Update a Zoho Inventory item
  *
  * @param array $item
  * @return array
  */
  public function itemUpdate($item);

  /**
  * Update a Zoho Inventory itemgroup
  *
  * @param array $itemGroup
  * @return array
  */
  public function itemGroupUpdate($itemGroup);

  /**
  * Delete a Zoho Inventory item
  *
  * @param int $itemId
  */
  public function itemDelete($itemId);

  /**
  * Ungroup a Zoho Inventory item
  *
  * @param int $groupId
  * @param int $itemId
  */
  public function itemGroup($groupId, $itemId);

  /**
  * Ungroup a Zoho Inventory item
  *
  * @param int $itemId
  */
  public function itemUngroup($itemId);

  /**
  * Mark a Zoho Inventory item as active
  *
  * @param int $itemId
  */
  public function itemSetActive($itemId);

  /**
  * Mark a Zoho Inventory item as inactive
  *
  * @param int $itemId
  */
  public function itemSetInactive($itemId);

  /**
  * Delete a Zoho Inventory item
  *
  * @param int $itemId
  */
  public function itemGroupDelete($itemGroup);

  /**
  * Mark a Zoho Inventory group as active
  *
  * @param int $groupId
  */
  public function itemGroupSetActive($groupId);

  /**
  * Mark a Zoho Inventory group as inactive
  *
  * @param int $groupId
  */
  public function itemGroupSetInactive($groupId);

  /**
  * Delete a Zoho Inventory item image
  *
  * @param int $itemId
  * return array
  */
  public function imageDelete($itemId);
}