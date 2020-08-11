<?php

namespace Drupal\seeds_layouts\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Layout Field item annotation object.
 *
 * @see \Drupal\seeds_layouts\Plugin\LayoutFieldManager
 * @see plugin_api
 *
 * @Annotation
 */
class LayoutField extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
