<?php
/**
 * Copyright © 2019 Roma Technology Limited. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace RTech\Zoho\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ZohoSalesOrderManagement extends AbstractDb {

  protected $_isPkAutoIncrement = false;

  protected function _construct() {
    $this->_init('zoho_sales_order_management', 'order_id');
  }

}