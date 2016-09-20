<?php

/**
 * @file
 * Contains \Drupal\external_entities\Entity\ExternalEntityTypeInterface.
 */

namespace Drupal\external_entities\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;


/**
 * Provides an interface defining a node type entity.
 */
interface ExternalEntityTypeInterface extends EntityWithPluginCollectionInterface, ConfigEntityInterface {

  /**
   * Gets the description.
   *
   * @return string
   *   The description of this external entity type.
   */
  public function getDescription();

  /**
   * Returns if entities of this external entity are read only.
   *
   * @return bool
   *   TRUE if the entities are read only, FALSE otherwise.
   */
  public function isReadOnly();

  /**
   * Returns the field mappings of this external entity type.
   *
   * @return array
   *   An array associative array:
   *     - key: The source property.
   *     - value: The destination field.
   */
  public function getFieldMappings();

  /**
   * Returns the field mapping for the given field of this external entity type.
   *
   * @return string|boolean
   *   The name of the property this field is mapped to. FALSE if the mapping
   *   doesn't exist.
   */
  public function getFieldMapping($field_name);

  /**
   * Returns the id of the external entity storage connection.
   *
   * @return string
   *   The external entity storage connection id.
   */
  public function getConnectionId();

  /**
   * Returns the external entity storage connection plugin.
   *
   * @return \Drupal\external_entities\Plugin\ExternalEntityStorageConnectionInterface
   *   The plugin storage connection used by this external entity type.
   */
  public function getConnection();

  /**
   * Sets the external entity storage connection plugin.
   *
   * @param string $external_entity_storage_connection_id
   *   The external entity storage connection plugin ID.
   */
  public function setConnection($external_entity_storage_connection_id);

  public function setConfiguration(array $configuration);

  /**
   * Returns the format in which the storage connection should make its requests.
   *
   * @return string
   *   The format in which the storage connection should make its requests.
   */
  public function getFormat();

}
