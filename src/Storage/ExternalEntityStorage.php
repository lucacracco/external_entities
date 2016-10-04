<?php

namespace Drupal\external_entities\Storage;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\external_entities\Decoder\ResponseDecoderFactoryInterface;
use Drupal\Core\Entity\ContentEntityStorageBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Defines the controller class for external entities.
 *
 * This extends the base storage class, adding required special handling for
 * external entities.
 */
class ExternalEntityStorage extends ContentEntityStorageBase {

  /**
   * The external storage connection manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $storageConnectionManager;

  /**
   * Storage connection instances.
   *
   * @var \Drupal\external_entities\Plugin\ExternalEntityStorageConnectionInterface[]
   */
  protected $storageConnections = [];

  /**
   * The decoder.
   *
   * @var \Drupal\external_entities\Decoder\ResponseDecoderFactoryInterface
   */
  protected $decoder;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('cache.entity'),
      $container->get('plugin.manager.external_entity_storage_connection'),
      $container->get('external_entity.storage_connection.response_decoder_factory')
    );
  }

  /**
   * Constructs a new ExternalEntityStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $storage_connection_manager
   *   The storage connection manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, CacheBackendInterface $cache, PluginManagerInterface $storage_connection_manager, ResponseDecoderFactoryInterface $decoder) {
    parent::__construct($entity_type, $entity_manager, $cache);
    $this->storageConnectionManager = $storage_connection_manager;
    $this->decoder = $decoder;
    $this->cacheBackend = $cache;
  }

  /**
   * Get the storage connection for a bundle.
   *
   * @param string $bundle_id
   *   The bundle to get the storage connection for.
   *
   * @return \Drupal\external_entities\Plugin\ExternalEntityStorageConnectionInterface
   *   The external entity storage connection.
   */
  protected function getStorageConnection($bundle_id) {
    if (!isset($this->storageConnections[$bundle_id])) {

      /** @var \Drupal\external_entities\Entity\ExternalEntityTypeInterface $bundle */
      $bundle = $this->entityManager->getStorage('external_entity_type')
        ->load($bundle_id);
      $this->storageConnections[$bundle_id] = $bundle->getConnection();
    }
    return $this->storageConnections[$bundle_id];
  }

  /**
   * Acts on entities before they are deleted and before hooks are invoked.
   *
   * Used before the entities are deleted and before invoking the delete hook.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   An array of entities.
   *
   * @throws EntityStorageException
   */
  public function preDelete(array $entities) {
    foreach ($entities as $entity) {
      /** @var \Drupal\external_entities\Entity\ExternalEntityTypeInterface $bundle */
      $bundle = $this->entityManager->getStorage('external_entity_type')
        ->load($entity->bundle());
      if ($bundle && $bundle->isReadOnly()) {
        throw new EntityStorageException($this->t('Can not delete read-only external entities.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {
    // Do the actual delete.
    foreach ($entities as $entity) {
      $this->getStorageConnection($entity->bundle())->delete($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $ids = NULL) {

    // Group by entity type bundle for retrieve information of cache implementation.
    $group_ids = [];
    foreach ($ids as $id) {
      if (strpos($id, '-')) {
        $tmp = explode("-", $id);
        $group_ids[$tmp[0]][] = $id;
      }
    }

    foreach($group_ids as $group_bundle => $ids) {

      /** @var \Drupal\external_entities\Entity\ExternalEntityTypeInterface $bundle */
      $bundle = $this->entityManager->getStorage('external_entity_type')->load($group_bundle);

      $entities_from_cache = [];
      if ($bundle->isCacheable()) {
        // Attempt to load entities from the persistent cache. This will remove IDs
        // that were loaded from $ids.
        $entities_from_cache = $this->getFromPersistentCache($ids);
      }

      // Load any remaining entities from the database.
      if ($entities_from_storage = $this->getFromStorage($ids)) {
        $this->invokeStorageLoadHook($entities_from_storage);
        $this->setPersistentCache($entities_from_storage);
      }
      return $entities_from_cache + $entities_from_storage;
    }

    return [];
  }

  /**
   * Gets entities from the storage.
   *
   * @param array|null $ids
   *   If not empty, return entities that match these IDs. Return all entities
   *   when NULL.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Array of entities from the storage.
   */
  protected function getFromStorage(array $ids = NULL) {
    $entities = [];

    if (!empty($ids)) {
      // Sanitize IDs. Before feeding ID array into buildQuery, check whether
      // it is empty as this would load all entities.
      $ids = $this->cleanIds($ids);
    }

    if ($ids === NULL || $ids) {
      foreach ($ids as $id) {

        $bundle = $id[0];
        $external_id = $id[1];

        $entities[implode('-', $id)] = $this->create([$this->entityType->getKey('bundle') => $bundle])
          ->mapObject($this->getStorageConnection($bundle)
            ->load($external_id))
          ->enforceIsNew(FALSE);
      }
    }

    return $entities;
  }

  /**
   * Acts on an entity before the presave hook is invoked.
   *
   * Used before the entity is saved and before invoking the presave hook.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @throws EntityStorageException
   */
  public function preSave(\Drupal\Core\Entity\EntityInterface $entity) {
    /** @var \Drupal\external_entities\Entity\ExternalEntityTypeInterface $bundle */
    $bundle = $this->entityManager->getStorage('external_entity_type')
      ->load($entity->bundle());
    if ($bundle && $bundle->isReadOnly()) {
      throw new EntityStorageException($this->t('Can not save read-only external entities.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
    return $this->getStorageConnection($entity->bundle())->save($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getQueryServiceName() {
    return 'entity.query.external';
  }

  /**
   * {@inheritdoc}
   */
  protected function has($id, EntityInterface $entity) {
    return !$entity->isNew();
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteFieldItems($entities) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteRevisionFieldItems(ContentEntityInterface $revision) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadRevisionFieldItems($revision_id) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doSaveFieldItems(ContentEntityInterface $entity, array $names = []) {
  }

  /**
   * {@inheritdoc}
   */
  protected function readFieldItemsToPurge(FieldDefinitionInterface $field_definition, $batch_size) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function purgeFieldItems(ContentEntityInterface $entity, FieldDefinitionInterface $field_definition) {
  }

  /**
   * {@inheritdoc}
   */
  public function countFieldData($storage_definition, $as_bool = FALSE) {
    return $as_bool ? 0 : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasData() {
    return FALSE;
  }

  /**
   * Ensures integer entity IDs are valid.
   *
   * The identifier sanitization provided by this method has been introduced
   * as Drupal used to rely on the database to facilitate this, which worked
   * correctly with MySQL but led to errors with other DBMS such as PostgreSQL.
   *
   * @param array $ids
   *   The entity IDs to verify.
   *
   * @return array
   *   The sanitized list of entity IDs.
   */
  protected function cleanIds(array $ids) {
    $definitions = $this->entityManager->getBaseFieldDefinitions($this->entityTypeId);
    $id_definition = $definitions[$this->entityType->getKey('id')];
    if ($id_definition->getType() == 'string') {
      $ids = array_filter($ids, function ($id) {
        return is_string($id) && strpos($id, '-');
      });

      $ids = array_map(function ($id) {
        return explode('-', $id);
      }, $ids);
    }
    return $ids;
  }

}
