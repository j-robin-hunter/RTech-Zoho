<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Model\Config\Source;

class FrontendBackend implements \Magento\Framework\Option\ArrayInterface {

  const BACKEND = 3;
  const FRONTEND = 2;
  const BOTH = 1;
  const NONE = 0;

  public function toOptionArray() {
    return [
      ['value' => $this::BACKEND, 'label' => __('Backend')], 
      ['value' => $this::FRONTEND, 'label' => __('Frontend')], 
      ['value' => $this::BOTH, 'label' => __('Both')],
      ['value' => $this::NONE, 'label' => __('None')]
    ];
  }

  public function toArray() {
    return [
      $this::NONE => __('None'), 
      $this::BOTH => __('Both'), 
      $this::FRONTEND => __('Frontend'), 
      $this::BACKEND => __('Backend')
    ];
  }
}