<?php

namespace Drupal\seeds_layouts\Plugin\LayoutField;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\seeds_layouts\Plugin\LayoutFieldBase;
use Drupal\seeds_layouts\SeedsLayoutsManager;

/**
 * Provides a 'select' field.
 *
 * @LayoutField(
 *   id = "select",
 *   label = @Translation("Select")
 * )
 */
class SelectField extends LayoutFieldBase implements ContainerFactoryPluginInterface {

  /**
   * Seeds layout manager.
   *
   * @var \Drupal\seeds_layouts\SeedsLayoutsManager
   */
  protected $seedsLayoutManager;

  /**
   * {@inheritDoc}.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SeedsLayoutsManager $seeds_layout_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->seedsLayoutManager = $seeds_layout_manager;
  }

  /**
   * {@inheritDoc}.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get("seeds_layouts.manager")
    );
  }

  /**
   * {@inheritDoc}.
   */
  public function getAttributes() {
    return [
      'class' => [$this->getConfiguration('class')],
    ];
  }

  /**
   * {@inheritDoc}.
   */
  public function build(array $form, FormStateInterface $form_state) {
    // Parse the classes string into an array.
    $options = $this->seedsLayoutManager->parseClassList($this->getConfiguration('classes'));

    $form['class'] = [
      '#type' => 'select',
      '#title' => $this->getLabel(),
      '#default_value' => $this->getConfiguration('class'),
      '#options' => $options,
    ];
    return $form;
  }

  /**
   * {@inheritDoc}.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, $columns = NULL) {
    $form['classes'] = [
      '#type' => 'textarea',
      '#required' => TRUE,
      '#title' => t("Classes"),
      '#default_value' => $this->getConfiguration('classes'),
      '#description' => t('Used as: @example', ['@example' => '"class|Label"']),
    ];
    return $form;
  }

}
