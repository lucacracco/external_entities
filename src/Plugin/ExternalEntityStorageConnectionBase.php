<?php

namespace Drupal\external_entities\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for external entity storage connections.
 */
abstract class ExternalEntityStorageConnectionBase extends PluginBase implements ContainerFactoryPluginInterface, ExternalEntityStorageConnectionInterface {

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
      $container->get('external_entity.storage_client.response_decoder_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, \Drupal\external_entities\Decoder\ResponseDecoderFactoryInterface $decoder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
  public function calculateDependencies() {
    return [];
  }

}
