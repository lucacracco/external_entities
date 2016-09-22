<?php

namespace Drupal\external_entities\Plugin;

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
      $container->get('external_entity.storage_connection.response_decoder_factory'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, \Drupal\external_entities\Decoder\ResponseDecoderFactoryInterface $decoder, \GuzzleHttp\ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $decoder);
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      "default_limit" => 10,
      "page_parameter" => "page",
      "page_size_parameter" => "pagesize",
      "page_parameter_type" => "pagenum",
      "page_size_parameter_type" => "pagesize",
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

    $form['pager_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Pager settings'),
      '#open' => FALSE,
    ];
    $form['pager_settings']['default_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default number of items per page'),
      '#required' => FALSE,
      '#default_value' => $configuration['default_limit'],
    ];
    $form['pager_settings']['page_parameter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page parameter'),
      '#required' => FALSE,
      '#default_value' => $configuration['page_parameter'],
    ];
    $form['pager_settings']['page_parameter_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Page parameter type'),
      '#required' => FALSE,
      '#options' => [
        'pagenum' => $this->t('Page number'),
        'startitem' => $this->t('Starting item'),
      ],
      '#description' => $this->t('Use "Page number" when the pager uses page numbers to determine the item to start at, use "Starting item" when the pager uses the item number to start at.'),
      '#default_value' => $configuration['page_parameter_type'],
    ];
    $form['pager_settings']['page_size_parameter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page size parameter'),
      '#required' => FALSE,
      '#default_value' => $configuration['page_size_parameter'],
    ];
    $form['pager_settings']['page_size_parameter_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Page size parameter type'),
      '#required' => FALSE,
      '#options' => [
        'pagesize' => $this->t('Number of items per page'),
        'enditem' => $this->t('Ending item'),
      ],
      '#description' => $this->t('Use "Number of items per pager" when the pager uses this parameter to determine the amount of items on each page, use "Ending item when the pager uses this parameter to determine the number of the last item on the page.'),
      '#default_value' => $configuration['page_size_parameter_type'],
    ];

    $form['api_key_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API key settings'),
      '#open' => FALSE,
    ];
    $form['api_key_settings']['header_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header name'),
      '#description' => $this->t('The HTTP header name for the API key. Leave blank if no API key is required.'),
      '#required' => FALSE,
      '#default_value' => $configuration['header_name'],
    ];
    $form['api_key_settings']['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#description' => $this->t('The API key needed to communicate with the entered endpoint. Leave blank if no API key is required.'),
      '#required' => FALSE,
      '#default_value' => $configuration['key'],
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
      '#description' => t('Enter the parameters to add to the endpoint URL when loading the list of entities. One per line in the format "parameter_name|parameter_value"'),
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

    // EndPoint.
//    $form_state->setValue('endpoint', $form_state->getValue('endpoint'));

    // Pager Settings.
    foreach ($form_state->getValue('pager_settings') as $key => $value) {
      $form_state->setValue($key, $value);
    }
    $form_state->unsetValue('pager_settings');

    // API Key Settings.
    foreach ($form_state->getValue('api_key_settings') as $key => $value) {
      $form_state->setValue($key, $value);
    }
    $form_state->unsetValue('api_key_settings');

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
