<?php

namespace Drupal\external_entities\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for external entity storage connections.
 */
abstract class ExternalEntityStorageConnectionBase extends PluginBase implements ContainerFactoryPluginInterface, ExternalEntityStorageConnectionInterface {

  /**
   * The machine name of the entity bundle using this plugin.
   *
   * @var string
   */
  protected $externalEntityType;

  /**
   * The entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * The decoder to decode the data.
   *
   * @var \Drupal\external_entities\Decoder\ResponseDecoderFactoryInterface
   */
  protected $decoder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('external_entity.storage_client.response_decoder_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, \Drupal\external_entities\Decoder\ResponseDecoderFactoryInterface $decoder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityStorage = $entity_manager->getStorage('external_entity_type');
    $this->decoder = $decoder;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getHttpHeaders() {
    return isset($this->configuration['http_headers']) ? $this->configuration['http_headers'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setExternalEntity($external_entity_type) {
    $this->externalEntityType = $external_entity_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  protected function getExternalEntityType() {
    return $this->entityStorage->load($this->externalEntityType);
  }
}
