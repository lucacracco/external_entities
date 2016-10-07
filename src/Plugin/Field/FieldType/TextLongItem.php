<?php

namespace Drupal\external_entities\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'text_long' field type.
 *
 * @FieldType(
 *   id = "external_text_long",
 *   label = @Translation("Exernal Text (formatted, long)"),
 *   description = @Translation("This field stores a long text with a text format."),
 *   category = @Translation("External"),
 *   default_widget = "text_textarea",
 *   default_formatter = "text_default"
 * )
 */
class TextLongItem extends TextItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array();
  }
}
