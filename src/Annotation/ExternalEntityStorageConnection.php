<?php

/**
 * @file
 * Contains \Drupal\external_entities\Annotation\ExternalEntityStorageConnection.
 */

namespace Drupal\external_entities\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an external entity storage connection annotation object
 *
 * @see \Drupal\external_entities\ExternalEntityStorageConnectionManager
 * @see plugin_api
 *
 * @Annotation
 */
class ExternalEntityStorageConnection extends Plugin {
  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the provider that owns the filter.
   *
   * @var string
   */
  public $provider;

  /**
   * The name of the storage connection.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * Additional administrative information.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

  /**
   * The default settings for plugin.
   *
   * @var array (optional)
   */
  public $settings = [];

}
