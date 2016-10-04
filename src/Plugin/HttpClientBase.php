<?php

namespace Drupal\external_entities\Plugin;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base for form plugin connection derived from original module.
 */
abstract class HttpClientBase extends ConfigurableExternalEntityStorageConnectorBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

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
      $container->get('entity.manager'),
      $container->get('external_entity.storage_connection.response_decoder_factory'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, \Drupal\external_entities\Decoder\ResponseDecoderFactoryInterface $decoder, \GuzzleHttp\ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $decoder);
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'endpoint' => '',
      "header_name" => NULL,
      "key" => NULL,
      "list" => [],
      "single" => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $definition = $this->getPluginDefinition();
    $configuration = $this->getConfiguration();

    $form['description'] = [
      '#type' => 'item',
      '#title' => $this->t('Connection description'),
      '#plain_text' => $definition['description'],
      '#prefix' => '<hr/>',
    ];

    $form['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint for %name', ['%name' => $this->getName()]),
      '#required' => TRUE,
      '#default_value' => $configuration['endpoint'],
      '#size' => 255,
      '#maxlength' => 255,
    ];

    $form['parameters'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Parameters'),
      '#open' => FALSE,
    ];
    $list_lines = [];
    foreach ($configuration['list'] as $parameter => $value) {
      $list_lines[] = "$parameter|$value";
    }
    $form['parameters']['list'] = [
      '#type' => 'textarea',
      '#title' => t('List parameters'),
      '#description' => t('Enter the parameters to add to the endpoint URL when loading the list of entities. One per line in the format "parameter_name|parameter_value".'),
      '#default_value' => implode("\n", $list_lines),
    ];
    $single_lines = [];
    foreach ($configuration['single'] as $parameter => $value) {
      $single_lines[] = "$parameter|$value";
    }
    $form['parameters']['single'] = [
      '#type' => 'textarea',
      '#title' => t('Single parameters'),
      '#description' => t('Enter the parameters to add to the endpoint URL when loading a single of entities. One per line in the format "parameter_name|parameter_value"'),
      '#default_value' => implode("\n", $single_lines),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

    // Remove the description form item element value so it will not persist.
    $form_state->unsetValue('description');

    // Validation of Parameters.
    foreach (['list', 'single'] as $type) {
      $string = $form_state->getValue(['parameters', $type]);
      $parameters = [];
      $list = explode("\n", $string);
      $list = array_map('trim', $list);
      $list = array_filter($list, 'strlen');
      foreach ($list as $text) {
        // Check for an explicit key.
        $matches = [];
        if (preg_match('/(.*)\|(.*)/', $text, $matches)) {
          // Trim key and value to avoid unwanted spaces issues.
          $key = trim($matches[1]);
          $value = trim($matches[2]);
        }
        // Otherwise see if we can use the value as the key.
        else {
          $key = $value = $text;
        }
        $parameters[$key] = $value;
      }

      $form_state->setValue($type, $parameters);
    }
    $form_state->unsetValue('parameters');
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {

    // Get all settings and save in plugin.
    $settings = $form_state->getValues();
    foreach ($settings as $key => $value) {
      $this->configuration[$key] = $value;
    }
  }

}
