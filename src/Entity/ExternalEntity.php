<?php

/**
 * @file
 * Contains \Drupal\external_entities\Entity\ExternalEntity.
 */

namespace Drupal\external_entities\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\external_entities\ExternalEntityInterface;

/**
 * Defines the external enttiy entity class.
 *
 * @ContentEntityType(
 *   id = "external_entity",
 *   label = @Translation("External entity"),
 *   bundle_label = @Translation("External entity type"),
 *   handlers = {
 *     "storage" = "Drupal\external_entities\ExternalEntityStorage",
 *     "storage_schema" = "Drupal\external_entities\ExternalEntityStorageSchema",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "access" = "Drupal\external_entities\ExternalEntityAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\external_entities\ExternalEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "edit" = "Drupal\external_entities\ExternalEntityForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\external_entities\Entity\ExternalEntityRouteProvider",
 *     },
 *     "list_builder" = "Drupal\external_entities\ExternalEntityListBuilder",
 *   },
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 *   bundle_entity_type = "external_entity_type",
 *   field_ui_base_route = "entity.external_entity_type.edit_form",
 *   common_reference_target = TRUE,
 *   permission_granularity = "bundle",
 *   links = {
 *     "canonical" = "/external-entity/{external_entity}",
 *     "delete-form" = "/external-entity/{external_entity}/delete",
 *     "edit-form" = "/external-entity/{external_entity}/edit",
 *     "collection" = "/external-entity",
 *   }
 * )
 */
class ExternalEntity extends ContentEntityBase implements ExternalEntityInterface {

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->bundle() . '-' . parent::id();
  }

  /**
   * {@inheritdoc}
   */
  public function externalId() {
    return parent::id();
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);
    if (method_exists($storage, 'preDelete')) {
      $storage->preDelete($entities);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if (method_exists($storage, 'preSave')) {
      $storage->preSave($this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('External Entity ID'))
      ->setDescription(t('The external entity ID.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The external entity UUID.'))
      ->setReadOnly(TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The external entity type.'))
      ->setSetting('target_type', 'external_entity_type')
      ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setRevisionable(FALSE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappedObject() {
    $bundle = $this->entityManager()->getStorage('external_entity_type')->load($this->bundle());
    $object = new \stdClass();
    foreach ($bundle->getFieldMappings() as $source => $destination) {
      $field_definition = $this->getFieldDefinition($source);
      $settings = $field_definition->getSettings();
      $property = $field_definition->getFieldStorageDefinition()->getMainPropertyName();

      // Special case for references to external entities.
      if (isset($settings['target_type']) && $settings['target_type'] === 'external_entity') {
        // Only 1 bundle is allowed.
        $target_bundle = reset($settings['handler_settings']['target_bundles']);
        $object->{$destination} = substr($this->get($source)->{$property}, strlen($target_bundle) + 1);
      }
      else {
        $object->{$destination} = $this->get($source)->{$property};
      }
    }
    return $object;
  }

  /**
   * {@inheritdoc}
   */
  public function mapObject(\stdClass $object) {
    $bundle = $this->entityManager()->getStorage('external_entity_type')->load($this->bundle());

    foreach ($bundle->getFieldMappings() as $destination => $source) {
      $field_definition = $this->getFieldDefinition($destination);
      $settings = $field_definition->getSettings();
      $property = $field_definition->getFieldStorageDefinition()->getMainPropertyName();

      // Special case for references to external entities.
      if (isset($settings['target_type']) && $settings['target_type'] === 'external_entity') {
        // Only 1 bundle is allowed.
        $target_bundle = reset($settings['handler_settings']['target_bundles']);
        $this->get($destination)->{$property} = $target_bundle . '-' . $object->{$source};
      }
      else {
        $this->get($destination)->{$property} = $object->{$source};
      }
    }
    return $this;
  }

}
