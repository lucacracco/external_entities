<?php

namespace Drupal\external_entities\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for node type forms.
 */
class ExternalEntityTypeForm extends EntityForm {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The ExternalEntityStorageConnectionManager.
   *
   * @var \Drupal\external_entities\Plugin\ExternalEntityStorageConnectionManager
   */
  protected $manager;

  /**
   * Decoder Factory.
   *
   * @var \Drupal\external_entities\Decoder\ResponseDecoderFactoryInterface
   */
  protected $decoderFactory;

  /**
   * Constructs the NodeTypeForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager
   */
  public function __construct(
    EntityManagerInterface $entity_manager,
    \Drupal\external_entities\Plugin\ExternalEntityStorageConnectionManager $manager,
    \Drupal\external_entities\Decoder\ResponseDecoderFactoryInterface $decoder_factory) {
    $this->entityManager = $entity_manager;
    $this->manager = $manager;
    $this->decoderFactory = $decoder_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('plugin.manager.external_entity_storage_connection'),
      $container->get('external_entity.storage_connection.response_decoder_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    /** @var \Drupal\external_entities\Entity\ExternalEntityTypeInterface $type */
    $type = &$this->entity;
    $type->getPluginCollections();

    // Disable caching on this form.
    $form_state->setCached(FALSE);
    $form = parent::form($form, $form_state);
    $form['#tree'] = TRUE;

    if ($this->operation == 'add') {
      $form['#title'] = Html::escape($this->t('Add external entity type'));
      $base_fields = $this->entityManager->getBaseFieldDefinitions('external_entity');
      $fields = $this->entityManager->getFieldDefinitions('external_entity', $type->id());
      // Create an external entity with a fake bundle using the type's UUID so
      // that we can get the default values for workflow settings.
      // @todo Make it possible to get default values without an entity.
      //   https://www.drupal.org/node/2318187
      $node = $this->entityManager->getStorage('external_entity')
        ->create(['type' => $type->uuid()]);
    }
    else {
      $form['#title'] = $this->t('Edit %label external entity type', ['%label' => $type->label()]);
      $base_fields = $this->entityManager->getFieldDefinitions('external_entity', $type->id());
      $fields = $this->entityManager->getFieldDefinitions('external_entity', $type->id());
      // Create a node to get the current values for workflow settings fields.
      $node = $this->entityManager->getStorage('external_entity')
        ->create(['type' => $type->id()]);
    }
    unset($fields[$this->entityManager->getDefinition('external_entity')
        ->getKey('uuid')]);
    unset($fields[$this->entityManager->getDefinition('external_entity')
        ->getKey('bundle')]);

    $form['label'] = [
      '#title' => $this->t('Name'),
      '#type' => 'textfield',
      '#default_value' => $type->label(),
      '#description' => $this->t('The human-readable name of this external entity type. This name must be unique.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['type'] = [
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => !$type->isNew(),
      '#machine_name' => [
        'exists' => [
          '\Drupal\external_entities\Entity\ExternalEntityType',
          'load'
        ],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this external entity type. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $type->getDescription(),
      '#description' => $this->t('Describe this external entity type.'),
    ];

    $form['read_only'] = [
      '#title' => $this->t('Read only'),
      '#type' => 'checkbox',
      '#default_value' => $type->isReadOnly(),
      '#description' => $this->t('Wheter or not this external entity type is read only.'),
    ];

    $form['additional_settings'] = [
      '#type' => 'vertical_tabs',
      '#attached' => [
        'library' => ['node/drupal.content_types'],
      ],
    ];

    $form['field_mappings'] = [
      '#type' => 'details',
      '#title' => $this->t('Field mappings'),
      '#group' => 'additional_settings',
      '#open' => TRUE,
    ];

    foreach ($fields as $field) {
      $form['field_mappings'][$field->getName()] = [
        '#title' => $field->getLabel(),
        '#type' => 'textfield',
        '#default_value' => $type->getFieldMapping($field->getName()),
        '#required' => isset($base_fields[$field->getName()]),
      ];
    }
    $form['connection_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Connection settings'),
      '#group' => 'additional_settings',
      '#open' => FALSE,
    ];

    $plugins = $this->manager->getDefinitions();
    $connection_options = [];
    foreach ($plugins as $connection) {
      $connection_options[$connection['id']] = $connection['name'];
    }
    $form['connection_settings']['connection'] = [
      '#type' => 'select',
      '#title' => $this->t('Connection'),
      '#options' => $connection_options,
      '#required' => TRUE,
      '#default_value' => $type->getConnectionId(),
      '#ajax' => [
        'callback' => [$this, 'updateConnection'],
        'wrapper' => 'connection-configuration',
        'progress' => [
          'type' => 'throbber',
          'message' => "searching",
        ],
      ],
    ];

    if ($type->getConnection() instanceof PluginFormInterface) {
      $form['connection_settings']['connection_configuration'] = [
        '#type' => 'details',
        '#title' => $this->t('Connection settings'),
        '#open' => TRUE,
        '#attributes' => ['id' => 'connection-configuration'],
      ];
      $form_connection_configuration = $type->getConnection()
        ->buildConfigurationForm([], $form_state);
      $form['connection_settings']['connection_configuration'] = NestedArray::mergeDeep($form['connection_settings']['connection_configuration'], $form_connection_configuration);
    }

    // Format Settings.
    $form['format_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Format settings'),
      '#group' => 'additional_settings',
      '#open' => FALSE,
    ];

    $formats = $this->decoderFactory->supportedFormats();
    $form['format_settings']['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Format'),
      '#options' => array_combine($formats, $formats),
      '#required' => TRUE,
      '#default_value' => $type->getFormat(),
    ];

    return $form;
  }

  /**
   * Implements callback for Ajax event on connection selection.
   *
   * @param array $form
   *   From render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   *
   * @return array
   *   Connection settings section of the form.
   */
  public function updateConnection(array &$form, FormStateInterface $form_state) {

    // Rebuild form to have updated options in cache.
    $form_state->setRebuild();

    /** @var \Drupal\external_entities\Entity\ExternalEntityTypeInterface $type */
    $type = &$this->entity;

    // Get connection select.
    $connection = $form_state->getValue(['connection_settings', 'connection']);

    // Set Connection for ExternalEntityType.
    $type->setConnection($connection);

    // Build Form configuration.
    if ($type->getConnection() instanceof PluginFormInterface) {
      $form['connection_settings']['connection_configuration'] = [
        '#type' => 'details',
        '#title' => $this->t('Connection settings'),
        '#open' => TRUE,
        '#attributes' => ['id' => 'connection-configuration'],
      ];
      $form_connection_configuration = $type->getConnection()
        ->buildConfigurationForm([], $form_state);
      $form['connection_settings']['connection_configuration'] = NestedArray::mergeDeep($form['connection_settings']['connection_configuration'], $form_connection_configuration);
    }

    return $form['connection_settings']['connection_configuration'];
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Save external entity type');
    $actions['delete']['#value'] = t('Delete external entity type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\external_entities\Entity\ExternalEntityTypeInterface $type */
    $type = &$this->entity;

    $id = trim($form_state->getValue('type'));
    // '0' is invalid, since elsewhere we check it using empty().
    if ($id == '0') {
      $form_state->setErrorByName('type', $this->t("Invalid machine-readable name. Enter a name other than %invalid.", ['%invalid' => $id]));
    }

    // Set field_mappings.
    $form_state->setValue('field_mappings', array_filter($form_state->getValue('field_mappings')));

    // Set format.
    $form_state->setValue('format', $form_state->getValue([
      'format_settings',
      'format'
    ]));

    // Set connection.
    $form_state->setValue('connection', $form_state->getValue([
      'connection_settings',
      'connection'
    ]));

    // Validation for plugin connection.
    if ($type->getConnection() instanceof PluginFormInterface) {

      // The External Entity form puts all connection plugin form elements in the
      // settings form element, so just pass that to the connection for validation.
      $settings = $form_state->getValue([
        'connection_settings',
        'connection_configuration'
      ]);
      $settings = (new FormState())->setValues($settings);

      $type->getConnection()->validateConfigurationForm($form, $settings);
      // Update the original form values.
      $form_state->setValue([
        'connection_settings',
        'connection_configuration'
      ], $settings->getValues());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\external_entities\Entity\ExternalEntityTypeInterface $type */
    $type = &$this->entity;

    if ($type->getConnection() instanceof PluginFormInterface) {

      // The External Entity form puts all connection plugin form elements in the
      // settings form element, so just pass that to the connection for validation.
      $settings = (new FormState())->setValues($form_state->getValue([
        'connection_settings',
        'connection_configuration'
      ]));

      // Submit configuration form plugin.
      $type->getConnection()->submitConfigurationForm($form, $settings);

      // Retrieve configuration plugin and save into entity.
      $type->setConfiguration($type->getConnection()->getConfiguration());

    }
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    /** @var \Drupal\external_entities\Entity\ExternalEntityTypeInterface $type */
    $type = &$this->entity;
    $type->set('type', trim($type->id()));
    $type->set('label', trim($type->label()));
    $status = $type->save();

    $t_args = ['%name' => $type->label()];

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('The external entity type %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message(t('The external entity type %name has been added.', $t_args));
      $context = array_merge($t_args, ['link' => $type->toLink($this->t('View'), 'collection')->toString()]);
      $this->logger('external_entities')
        ->notice('Added external entity type %name.', $context);
    }
    $this->entityManager->clearCachedFieldDefinitions();
    $form_state->setRedirectUrl($type->toUrl('collection'));
  }

}
