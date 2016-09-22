<?php

namespace Drupal\external_entities\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides an interface for a configurable External Storage Connection plugin.
 */
interface ConfigurableExternalEntityStorageConnectionInterface extends ConfigurablePluginInterface, PluginFormInterface, ExternalEntityStorageConnectionInterface {

}
