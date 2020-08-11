<?php

namespace Drupal\seeds_layouts\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for Layout Field plugins.
 */
abstract class LayoutFieldBase extends PluginBase implements LayoutFieldInterface {

  /**
   * {@inheritDoc}.
   */
  public function getAttributes() {
    return [];
  }

  /**
   * {@inheritDoc}.
   */
  public function build(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritDoc}.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritDoc}.
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    return TRUE;
  }

  /**
   * {@inheritDoc}.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritDoc}.
   */
  public function getPluginLabel() {
    return $this->getPluginDefinition()['label'];
  }

  /**
   * {@inheritDoc}.
   */
  public function getLabel() {
    return $this->configuration['description']['label'];
  }

  /**
   * {@inheritDoc}.
   */
  public function getConfiguration($key = NULL) {
    if ($key !== NULL) {
      return isset($this->configuration[$key]) ? $this->configuration[$key] : NULL;
    }

    return $this->configuration;
  }

  /**
   * {@inheritDoc}.
   */
  public function getLibraries() {
    return [];
  }

}
