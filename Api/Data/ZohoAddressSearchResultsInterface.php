<?php
/**
 * Copyright © 2019 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Api\Data;

interface ZohoAddressSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface {
    /**
     * Get customer addresses list.
     *
     * @return \RTech\Zoho\Api\Data\ZohoAddressInterface[]
     */
    public function getItems();

    /**
     * Set customer addresses list.
     *
     * @param \RTechZoho\Api\Data\ZohoAddressInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}