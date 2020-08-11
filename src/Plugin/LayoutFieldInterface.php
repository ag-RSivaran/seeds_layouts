<?php

namespace Drupal\seeds_layouts\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines an interface for Layout Field plugins.
 */
interface LayoutFieldInterface extends PluginInspectionInterface, PluginFormInterface {

  /**
   * Gets the attributes of the field.
   *
   * ```php
   * return [
   *    'class' => [
   *      'custom1',
   *      $this->configuration['my_own_class_from_the_build()_form']
   *    ]
   * ];
   * ```
   *
   * @return array
   *   An array of attributes.
   */
  public function getAttributes();

  /**
   * Builds the field element in the section configurations form.
   *
   * @return array
   *   The render array.
   */
  public function build(array $form, FormStateInterface $form_state);

  /**
   * Gets the label of the plugin.
   *
   * @return string
   *   The label of the plugin.
   */
  public function getPluginLabel();

  /**
   * Gets the plugin description.
   *
   * @return string
   *   The plugin description
   */
  public function getLabel();

  /**
   * Gets the configuration.
   *
   * @param string $key
   *   If NULL, gets all configuration.
   *
   * @return mixed
   *   The configuration value
   */
  public function getConfiguration($key);

  /**
   * Gets the attached libraries of this field.
   *
   * @return array
   *   The attached libraries
   */
  public function getLibraries();

}
