<?php

namespace Drupal\external_entities\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Plugin implementation of the 'text' field type for external entity.
 *
 * @FieldType(
 *   id = "external_text",
 *   label = @Translation("External Text (formatted)"),
 *   description = @Translation("This field stores a text with a text format."),
 *   category = @Translation("External"),
 *   default_widget = "text_textfield",
 *   default_formatter = "text_default"
 * )
 */
class TextItem extends TextItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [];
  }

}
