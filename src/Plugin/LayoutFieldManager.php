<?php

namespace Drupal\seeds_layouts\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the Layout Field plugin manager.
 */
class LayoutFieldManager extends DefaultPluginManager {

  /**
   * Constructs a new LayoutFieldManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/LayoutField', $namespaces, $module_handler, 'Drupal\seeds_layouts\Plugin\LayoutFieldInterface', 'Drupal\seeds_layouts\Annotation\LayoutField');

    $this->alterInfo('seeds_layouts_layout_field_info');
    $this->setCacheBackend($cache_backend, 'seeds_layouts_layout_field_plugins');
  }

  /**
   * Gets all layout field plugins as select options.
   *
   * @return array
   *   An array of all plugins as select options.
   */
  public function getOptions() {
    $defs = $this->getDefinitions();
    $options = [];
    foreach ($defs as $id => $def) {
      $options[$id] = $def['label'];
    }
    return $options;
  }

}
