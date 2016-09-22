<?php

/**
 * @file
 * Contains Drupal\external_entities\Plugin\ExternalEntityStorageConnectionInterface.
 */

namespace Drupal\external_entities\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\external_entities\Entity\ExternalEntityInterface;

/**
 * Defines an interface for external entity storage connection plugins.
 */
interface ExternalEntityStorageConnectionInterface extends PluginInspectionInterface {

  /**
   * Return the name of the external entity storage connection.
   *
   * @return string
   *   The name of the external entity storage connection.
   */
  public function getName();

  /**
   * Return the administrative description for this plugin.
   *
   * @return string
   *   Description of replicator.
   */
  public function getDescription();

  /**
   * Loads one entity.
   *
   * @param mixed $id
   *   The ID of the entity to load.
   *
   * @return \Drupal\external_entities\Entity\ExternalEntityInterface|null
   *   An external entity object. NULL if no matching entity is found.
   */
  public function load($id);

  /**
   * Saves the entity permanently.
   *
   * @param \Drupal\external_entities\Entity\ExternalEntityInterface $entity
   *   The entity to save.
   *
   * @return int
   *   SAVED_NEW or SAVED_UPDATED is returned depending on the operation
   *   performed.
   */
  public function save(ExternalEntityInterface $entity);

  /**
   * Deletes permanently saved entities.
   *
   * @param \Drupal\external_entities\Entity\ExternalEntityInterface $entity
   *   The external entity object to delete.
   */
  public function delete(ExternalEntityInterface $entity);

  /**
   * Query the external entities.
   *
   * @param array $parameters
   *   Key-value pairs of fields to query.
   */
  public function query(array $parameters);

  /**
   * Get HTTP headers to add.
   *
   * @return array
   *   Associative array of headers to add to the request.
   */
  public function getHttpHeaders();

  /**
   * Sets the External Entity Type ID for the connection using this plugin.
   *
   * @param string $external_entity_type
   *   The entity bundle ID.
   *
   * @return static
   */
  public function setExternalEntity($external_entity_type);
}
