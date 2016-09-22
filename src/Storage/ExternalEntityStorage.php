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

//      $connection_plugin = $this->storageConnectionManager->getDefinition($bundle->getConnection());
      // TODO: load plugin Connection.
//      $config = [
//        'http_client' => $this->httpClient,
//        'decoder' => $this->decoder,
//        'endpoint' => $client_plugin->getEndpoint(),
//        'format' => $client_plugin->getFormat(),
//        'http_headers' => [],
//        'parameters' => $client_plugin->getParameters(),
//      ];
//      $api_key_settings = $client_plugin->getApiKeySettings();
//      if (!empty($api_key_settings['header_name']) && !empty($api_key_settings['key'])) {
//        $config['http_headers'][$api_key_settings['header_name']] = $api_key_settings['key'];
//      }
//      $this->storageClients[$bundle_id] = $this->storageClientManager->createInstance(
//        $bundle->getClient(),
//        $config
//      );
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
    $entities = [];

    foreach ($ids as $id) {
      if (strpos($id, '-')) {
        list($bundle, $external_id) = explode('-', $id);
        $entities[$id] = $this->create([$this->entityType->getKey('bundle') => $bundle])
          ->mapObject($this->getStorageConnection($bundle)->load($external_id))
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

}
