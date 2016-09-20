<?php

namespace Drupal\external_entities\Plugin;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for external entity storage connector.
 */
abstract class ConfigurableExternalEntityStorageConnectorBase extends ExternalEntityStorageConnectionBase implements ContainerFactoryPluginInterface, ConfigurableExternalEntityStorageConnectionInterface {

  /**
   * The HTTP client to fetch the data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('external_entity.storage_connection.response_decoder_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, \GuzzleHttp\ClientInterface $http_client, \Drupal\external_entities\Decoder\ResponseDecoderFactoryInterface $decoder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $decoder);
    $this->httpClient = $http_client;
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
