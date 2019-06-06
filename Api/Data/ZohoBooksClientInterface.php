<?php
/**
* Copyright © 2018 Roma Technology Limited. All rights reserved.
* See COPYING.txt for license details.
*/
namespace RTech\Zoho\Api\Data;

interface ZohoBooksClientInterface {

  /**
  * Lookup a contact in Zoho Books
  *
  * @param string $displayName
  * @return array
  */
  public function lookupContact($displayName);

  /**
  * Add a contact to Zoho Books
  *
  * @param array $contact
  * @return array
  */
  public function addContact($contact);

  /**
  * Get a contact from Zoho Books
  *
  * @param int $contactId
  * @return array
  */
  public function getContact($contactId);

  /**
  * Update a contact to Zoho Books
  *
  * @param array $contact
  * @return array
  */
  public function updateContact($contact);

  /**
  * Delete a contact from Zoho Books
  *
  * @param array $contactId
  * @return array
  */
  public function deleteContact($contactId);

  /**
  * Mark a Zoho Books contact as inactive
  *
  * @param int $contactId
  */
  public function contactSetInactive($contactId);
}