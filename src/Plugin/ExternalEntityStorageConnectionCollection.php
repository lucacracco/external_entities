<?php

namespace Drupal\external_entities\Plugin;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Provides a collection of External Entity Storage Connection plugins.
 */
class ExternalEntityStorageConnectionCollection extends DefaultSingleLazyPluginCollection {

  /**
   * The unique ID for the entity bundle using this plugin collection.
   *
   * @var string
   */
  protected $externalEntityType;

  /**
   * Constructs a new ExternalEntityStorageConnectionCollection.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param string $instance_id
   *   The ID of the plugin instance.
   * @param array $configuration
   *   An array of configuration.
   * @param string $external_entity_type
   *   The unique ID of the External Entity Type using this plugin.
   */
  public function __construct(PluginManagerInterface $manager, $instance_id, array $configuration, $external_entity_type) {
    parent::__construct($manager, $instance_id, $configuration);
    $this->externalEntityType = $external_entity_type;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Block\BlockPluginInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    if (!$instance_id) {
      throw new PluginException("The External Entity Type did not specify a plugin.");
    }

    try {
      parent::initializePlugin($instance_id);
      $plugin_instance = $this->pluginInstances[$instance_id];
      if ($plugin_instance instanceof ExternalEntityStorageConnectionInterface) {
        $plugin_instance->setExternalEntity($this->externalEntityType);
      }
    } catch (PluginException $e) {
      $module = $this->configuration['provider'];
      // Ignore entity type belonging to uninstalled modules, but re-throw valid
      // exceptions when the module is installed and the plugin is
      // misconfigured.
      if (!$module || \Drupal::moduleHandler()->moduleExists($module)) {
        throw $e;
      }
    }
  }

}
