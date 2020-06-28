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
  * Add a composite item to Zoho Inventory
  *
  * @param array $compositeItem
  * @return array
  */
  public function itemCompositeAdd($compositeItem);

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
  * Delete a Zoho Inventory package
  *
  * @param int $packageId
  */
  public function packageDelete($packageId);

  /**
  * Add a shipment to Zoho Inventory
  *
  * @param array $package
  * @param array $shipment
  * @return array
  */
  public function shipmentAdd($package, $shipment);

  /**
  * Get a Zoho shipment
  *
  * @param string $shipmentId
  * @return array
  */
  public function getShipment($shipmentId);

  /**
  * Delete a Zoho Inventory shipment
  *
  * @param int $shipmentId
  */
  public function shipmentDelete($shipmentId);

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
  * Get a Zoho Inventory composite item
  *
  * @param int $compositeItemId
  * @return array
  */
  public function getCompositeItem($compositeItemId);

  /**
  * Get a Zoho Inventory composite item by name
  *
  * @param string $compositeName
  * @return array
  */
  public function getCompositeItemByName($compositeName);

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
  * Update a Zoho Inventory composite item
  *
  * @param array $compositeItem
  * @return array
  */
  public function itemCompositeUpdate($compositeItem);

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
  * Delete a Zoho Inventory item group
  *
  * @param int $groupId
  */
  public function itemGroupDelete($groupId);

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
  * Delete a Zoho Inventory composite item
  *
  * @param int $compositeItemId
  */
  public function itemCompositeDelete($compositeItemId);

  /**
  * Mark a Zoho Inventory composite item active
  *
  * @param int $compositeItemId
  */
  public function itemCompositeSetActive($compositeItemId);

  /**
  * Mark a Zoho Inventory composite item as inactive
  *
  * @param int $compositeItemId
  */
  public function itemCompositeSetInactive($compositeItemId);

  /**
  * Delete a Zoho Inventory item image
  *
  * @param int $itemId
  * return array
  */
  public function imageDelete($itemId);

  /**
  * Get Zoho sales return
  *
  * @param int $returnId
  * return array
  */  
  public function getSalesReturn($returnId);

  /**
  * Delete Zoho sales return
  *
  * @param int $returnId
  */  
  public function salesReturnDelete($returnId);

  /**
  * Delete Zoho sales return receivable
  *
  * @param int $receivableId
  */  
  public function salesReturnReceivableDelete($receivableId);

  /**
  * Add a credit note to Zoho Books based on a sales return
  *
  * @param string $salesReturnId
  * @param array $creditNote
  * @return array
  */
  public function addCreditNote($salesReturnId, $creditNote);

  /**
  * Apply credit notes to an invoice
  *
  * @param string $creditNoteId
  * @param array $amount
  */
  public function applyCredit($creditNoteId, $credits);
}