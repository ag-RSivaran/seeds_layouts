<?php

namespace Drupal\seeds_layouts\Plugin\LayoutField;

use Drupal\Core\Form\FormStateInterface;
use Drupal\seeds_layouts\Plugin\LayoutFieldBase;

/**
 * Provides a 'checkbox' field.
 *
 * @LayoutField(
 *   id = "checkbox",
 *   label = @Translation("Checkbox")
 * )
 */
class CheckboxField extends LayoutFieldBase {

  /**
   * {@inheritDoc}.
   */
  public function getAttributes() {
    if ((bool) $this->getConfiguration("checked")) {
      return [
        'class' => [$this->getConfiguration("class")],
      ];
    }

    return [];

  }

  /**
   * {@inheritDoc}.
   */
  public function build(array $form, FormStateInterface $form_state) {
    $form['checked'] = [
      '#type' => 'checkbox',
      '#title' => $this->getLabel(),
      '#default_value' => $this->getConfiguration("checked"),
    ];
    return $form;
  }

  /**
   * {@inheritDoc}.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['class'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => t("Class"),
      '#default_value' => $this->getConfiguration('class'),
    ];
    return $form;
  }

}
