<?php

namespace Drupal\seeds_layouts;

use Drupal\Component\Uuid\Php;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\seeds_layouts\Exception\FrameworkImportException;

/**
 * Class SeedsLayoutsManager.
 */
class SeedsLayoutsManager {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Config\FileStorage definition.
   *
   * @var \Drupal\Core\Config\FileStorage
   */
  protected $source;

  /**
   * Uuid instance.
   *
   * @var \Drupal\Component\Uuid\Php
   */
  protected $uuid;

  /**
   * Constructs a new SeedsLayoutsManager object.
   */
  public function __construct(EntityTypeManager $entity_type_manager, ConfigFactoryInterface $config_factory, Php $uuid) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $frameworks_config_path = drupal_get_path('module', 'seeds_layouts') . '/config/framework';
    $this->source = new FileStorage($frameworks_config_path);
    $this->uuid = $uuid;
  }

  /**
   * Convert a text of classes into an array using regex.
   *
   * @param string $classes
   *   The list of classes.
   *
   * @return array
   *   Returns an array of classes.
   */
  public static function classStringToArray($classes) {
    $keys = [];
    preg_match_all('/[^|\s]+\|[^|\s]+/', $classes, $keys);
    return $keys[0];
  }

  /**
   * Convert a text of attributes into an array using regex.
   *
   * @param string $attributes
   *   The list of attributes.
   *
   * @return array
   *   Returns an array of attributes.
   */
  public static function attributesStringToArray($attributes) {
    $keys = [];
    $values = [];
    preg_match_all('/([^\,]*\|[^\,]*)/', $attributes, $keys);
    $keys = $keys[1];
    foreach ($keys as $unparsed_key) {
      $unparsed_key = explode('|', $unparsed_key);
      $values[$unparsed_key[0]] = explode(' ', $unparsed_key[1]);
    }
    return $values;
  }

  /**
   * Parses a list of 'class|label' format.
   *
   * @param string $list
   *   The list of classes.
   */
  public static function parseClassList($list) {
    $options = [];
    $list = self::classStringToArray($list);
    foreach ($list as $match) {
      $match = explode('|', $match);
      $id = $match[0];
      $label = $match[1];
      $options[$id] = $label;
    }
    return $options;
  }

  /**
   * Sorts classes with regions.
   *
   * @param string $classes_string
   *   Classes as "class1,class2".
   * @param array $regions
   *   Array of regions.
   *
   * @return array
   *   An array of regions classes.
   */
  public static function sortClassesPerRegion($classes_string, array $regions) {
    $classes = explode(',', $classes_string);
    $region_classes = [];
    foreach ($regions as $name => $label) {
      $region_classes[$name] = current($classes);
      next($classes);
    }
    return $region_classes;
  }

  /**
   * Gets all supported frameworks.
   *
   * @return array
   *   An array of framework ids.
   */
  public function getSupportedFrameworks() {
    $frameworks = $this->source->listAll('framework');
    $supported_frameworks = [];
    foreach ($frameworks as $framework_yml) {
      $framework_config = $this->source->read($framework_yml);
      $supported_frameworks[$framework_config['id']] = $framework_config['label'];
    }
    return $supported_frameworks;
  }

  /**
   * Imports the config of a css framework if the theme supports it.
   *
   * @param string $framework_id
   *   The framework id.
   *
   * @throws \FrameworkImportException
   *
   * @return bool
   *   Returns TRUE if imported successfully, FALSE otherwise.
   */
  public function importFramework($framework_id) {
    // Get all frameworks in '/config/framework' folder.
    $frameworks = $this->source->listAll('framework');
    foreach ($frameworks as $framework_yml) {
      /** @var array $framework_config */
      if (!($framework_config = $this->source->read($framework_yml))) {
        throw new FrameworkImportException(sprintf("Can't access '%s', file not found or permission denied.", [$framework_yml]));
      };
      if ($framework_config['id'] == $framework_id) {
        // If we found our desired framework file, we unset the id
        // and the themes, they are not important for now.
        unset($framework_config['id']);
        unset($framework_config['themes']);

        $columns = $framework_config['columns'];

        // Replace $module_path.
        foreach ($columns as &$column) {
          $column['image_url'] = str_replace('{$module_path}', '/' . drupal_get_path('module', 'seeds_layouts'), $column['image_url']);
        }

        unset($framework_config['columns']);

        $this->configFactory->getEditable('seeds_layouts.config')->setData($framework_config)->save();
        $this->configFactory->getEditable('seeds_layouts.columns')->setData($columns)->save();
        return TRUE;
      }
    }
    throw new FrameworkImportException(sprintf("Could not find a framework with the id '%s'.", [$framework_id]));
  }

}
