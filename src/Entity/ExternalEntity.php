<?php

/**
 * @file
 * Contains \Drupal\external_entities\Entity\ExternalEntity.
 */

namespace Drupal\external_entities\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the external enttiy entity class.
 *
 * @ContentEntityType(
 *   id = "external_entity",
 *   label = @Translation("External entity"),
 *   bundle_label = @Translation("External entity type"),
 *   handlers = {
 *     "storage" = "Drupal\external_entities\Storage\ExternalEntityStorage",
 *     "storage_schema" = "Drupal\external_entities\Storage\ExternalEntityStorageSchema",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "access" = "Drupal\external_entities\Access\ExternalEntityAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\external_entities\Form\ExternalEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "edit" = "Drupal\external_entities\Form\ExternalEntityForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\external_entities\Routing\ExternalEntityRouteProvider",
 *     },
 *     "list_builder" = "Drupal\external_entities\ListBuilder\ExternalEntityListBuilder",
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
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappedObject() {
    /** @var \Drupal\external_entities\Entity\ExternalEntityTypeInterface $bundle */
    $bundle = $this->entityManager()
      ->getStorage('external_entity_type')
      ->load($this->bundle());
    $object = new \stdClass();
    foreach ($bundle->getFieldMappings() as $source => $destination) {
      $field_definition = $this->getFieldDefinition($source);
      $settings = $field_definition->getSettings();
      $property = $field_definition->getFieldStorageDefinition()
        ->getMainPropertyName();

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
  public function mapFromStorageRecords(\stdClass $object) {
    /** @var \Drupal\external_entities\Entity\ExternalEntityTypeInterface $bundle */
    $bundle = $this->entityManager()
      ->getStorage('external_entity_type')
      ->load($this->bundle());

    foreach ($this->getFieldDefinitions() as $field_name => $field_definition) {

      // Base Field Definition.
      if ($field_definition instanceof \Drupal\Core\Field\BaseFieldDefinition) {

        $source_mapping = $bundle->getFieldMapping($field_name);
        $property = $field_definition->getFieldStorageDefinition()
          ->getMainPropertyName();
        if ($source_mapping != FALSE && $source_mapping != '') {
          $data = $this->getDescendant($source_mapping, $object);
          $this->get($field_name)->{$property} = $data;
        }

      }
      // Field Definition.
      elseif ($field_definition instanceof \Drupal\field\Entity\FieldConfig) {

        $third_settings = $field_definition->getThirdPartySetting('external_entities', _external_entities_name_mapping_settings($field_definition, $field_name), NULL);
        $field_storage = $field_definition->getFieldStorageDefinition();
        $settings = $field_definition->getSettings();
        $properties = $field_storage->getPropertyNames();

        // Special case for references to external entities.
        if (isset($settings['target_type']) && $settings['target_type'] === 'external_entity') {
          $target_bundle = reset($settings['handler_settings']['target_bundles']);
          $property = "target_id";
          if (isset($third_settings[$property])) {
            $data = $this->getDescendant($third_settings[$property], $object);
            $this->get($field_name)->{$property} = $target_bundle . '-' . $data;
            continue;
          }
        }

        foreach ($properties as $property) {
          if (isset($third_settings[$property]) && $third_settings[$property] != '') {
            $data = $this->getDescendant($third_settings[$property], $object);
            $this->get($field_name)->{$property} = $data;
          }
        }
      }
    }

    return $this;
  }

  /**
   * @param $path
   * @param $var
   * @return mixed|null
   */
  public static function getDescendant($path, $var) {
    // Separate the path into an array of components
    $path_parts = explode('/', $path);

    // Loop over the parts of the path specified
    foreach ($path_parts as $property) {
      // Check that it's a valid access
      if (is_object($var) && isset($var->$property)) {
        // Traverse to the specified property,
        // overwriting the same variable
        $var = $var->$property;
      }
      elseif (is_array($var) && isset($var[$property])) {
        $var = $var[$property];
      }
      else {
        return NULL;
      }
    }

    // Our variable has now traversed the specified path
    return $var;
  }

}
