<?php

namespace Drupal\external_entities\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\external_entities\Plugin\ExternalEntityStorageConnectionCollection;

/**
 * Defines the External Entity type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "external_entity_type",
 *   label = @Translation("External Entity type"),
 *   handlers = {
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\external_entities\Form\ExternalEntityTypeForm",
 *       "edit" = "Drupal\external_entities\Form\ExternalEntityTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "list_builder" = "Drupal\external_entities\ListBuilder\ExternalEntityTypeListBuilder",
 *   },
 *   admin_permission = "administer external entity types",
 *   config_prefix = "type",
 *   bundle_of = "external_entity",
 *   entity_keys = {
 *     "id" = "type",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/external-entity-types/manage/{external_entity_type}",
 *     "delete-form" = "/admin/structure/external-entity-types/manage/{external_entity_type}/delete",
 *     "collection" = "/admin/structure/external-entity-types",
 *   },
 *   config_export = {
 *     "label",
 *     "type",
 *     "description",
 *     "read_only",
 *     "field_mappings",
 *     "connection",
 *     "configuration",
 *   }
 * )
 */
class ExternalEntityType extends ConfigEntityBundleBase implements ExternalEntityTypeInterface {

  /**
   * The machine name of this external entity type.
   *
   * @var string
   */
  protected $type;

  /**
   * The human-readable name of the external entity type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this external entity type.
   *
   * @var string
   */
  protected $description;

  /**
   * Whether or not entity types of this external entity type are read only.
   *
   * @var boolean
   */
  protected $read_only;

  /**
   * The field mappings for this external entity type.
   *
   * @var array
   */
  protected $field_mappings = [];

  /**
   * The ExternalEntityStorageConnection plugin id.
   *
   * @var string
   */
  protected $connection = 'rest_client';

  /**
   * The configuration of the ExternalEntityStorageConnection plugin.
   *
   * @var array
   */
  protected $configuration = [];

  /**
   * The plugin collection that stores ExternalEntityStorageConnection plugins.
   *
   * @var \Drupal\external_entities\Plugin\ExternalEntityStorageConnectionCollection
   */
  protected $connectionCollection;

  /**
   * The format in which to make the requests for this external entity type.
   *
   * For example: 'json'.
   *
   * @var string
   */
  protected $format = 'json';

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function isReadOnly() {
    return $this->read_only;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMappings() {
    return $this->field_mappings;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMapping($field_name) {
    return isset($this->field_mappings[$field_name]) ? $this->field_mappings[$field_name] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getConnection() {
    return $this->getPluginCollection()->get($this->connection);
  }

  /**
   * {@inheritdoc}
   */
  public function getConnectionId() {
    return $this->connection;
  }

  /**
   * {@inheritdoc}
   */
  public function setConnection($external_entity_storage_connection_id) {
    $this->connection = $external_entity_storage_connection_id;
    $this->getPluginCollection()
      ->addInstanceID($external_entity_storage_connection_id);
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormat() {
    return $this->format;
  }

  /**
   * Encapsulates the creation of the ExternalEntityType's LazyPluginCollection.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection
   *   The ExternalEntityType's plugin collection.
   */
  protected function getPluginCollection() {
    if (!$this->connectionCollection) {
      $this->connectionCollection = new ExternalEntityStorageConnectionCollection($this->externalEntityStorageConnectionManager(), $this->connection, $this->configuration, $this->id());
    }
    return $this->connectionCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['configuration' => $this->getPluginCollection()];
  }

  /**
   * Wraps the ExternalEntityStorageConnection plugin manager.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   *   A ExternalEntityStorageConnection plugin manager object.
   */
  protected function externalEntityStorageConnectionManager() {
    return \Drupal::service('plugin.manager.external_entity_storage_connection');
  }

}
