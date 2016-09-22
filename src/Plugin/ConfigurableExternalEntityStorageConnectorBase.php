<?php

namespace Drupal\external_entities\Plugin;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for external entity storage connector.
 */
abstract class ConfigurableExternalEntityStorageConnectorBase extends ExternalEntityStorageConnectionBase implements ContainerFactoryPluginInterface, ConfigurableExternalEntityStorageConnectionInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, \Drupal\external_entities\Decoder\ResponseDecoderFactoryInterface $decoder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $decoder);
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $this->configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * Gets this plugin's configuration.
   *
   * @return array
   *   An array of this plugin's configuration.
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'endpoint' => 'http://localhost',
    ];
  }


}
