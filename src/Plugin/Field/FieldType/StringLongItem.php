<?php

namespace Drupal\external_entities\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the 'string_long' field type for external entities.
 *
 * @FieldType(
 *   id = "external_string_long",
 *   label = @Translation("External Text (plain, long)"),
 *   description = @Translation("A field containing a long string value."),
 *   category = @Translation("External Text"),
 *   default_widget = "string_textarea",
 *   default_formatter = "basic_string",
 * )
 */
class StringLongItem extends StringItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
//    return array(
//      'columns' => array(
//        'value' => array(
//          'type' => $field_definition->getSetting('case_sensitive') ? 'blob' : 'text',
//          'size' => 'big',
//        ),
//      ),
//    );
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['value'] = $random->paragraphs();
    return $values;
  }

}
