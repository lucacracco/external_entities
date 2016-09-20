<?php

/**
 * @file
 * Contains \Drupal\external_entities\Plugin\ExternalEntityStorageConnectionManager.
 */

namespace Drupal\external_entities\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * ExternalEntityStorageConnectionManager plugin manager.
 */
class ExternalEntityStorageConnectionManager extends DefaultPluginManager {
  /**
   * Constructs an ExternalEntityStorageConnectionManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/ExternalEntityStorageConnection',
      $namespaces,
      $module_handler,
      'Drupal\external_entities\Plugin\ExternalEntityStorageConnectionInterface',
      'Drupal\external_entities\Annotation\ExternalEntityStorageConnection'
    );
    $this->alterInfo('external_entity_storage_connection_info');
    $this->setCacheBackend($cache_backend, 'external_entity_storage_connection');
  }
}
