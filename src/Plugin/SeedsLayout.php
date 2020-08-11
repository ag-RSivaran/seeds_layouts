<?php

namespace Drupal\seeds_layouts\Plugin;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\seeds_layouts\SeedsLayoutsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Layout class for all Display Suite layouts.
 */
class SeedsLayout extends LayoutDefault implements PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * Defines if the current user uses user friendly layout.
   *
   * @var bool
   */
  protected $userFriendly;

  /**
   * Configuration of seeds layouts.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $seedsLayoutConfig;

  /**
   * Configuration of seeds layouts styles.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $seedsColumnsConfig;

  /**
   * Seeds layouts manager.
   *
   * @var \Drupal\seeds_layouts\SeedsLayoutsManager
   */
  protected $seedsLayoutsManager;

  /**
   * Layout field manager.
   *
   * @var LayoutFieldManager
   */
  protected $layoutFieldManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, SeedsLayoutsManager $seeds_layouts_manager, LayoutFieldManager $layout_field_manager, AccountProxyInterface $current_user, EntityTypeManager $entity_type_manager) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->seedsLayoutConfig = $config_factory->get('seeds_layouts.config');
    $this->seedsLayoutEditableConfig = $config_factory->getEditable('seeds_layouts.config');
    $this->seedsColumnsConfig = $config_factory->get('seeds_layouts.columns');
    $this->seedsLayoutsManager = $seeds_layouts_manager;
    $this->layoutFieldManager = $layout_field_manager;
    $this->currentUser = $current_user;
    $this->userFriendly = !$current_user->hasPermission("access advanced seeds layouts settings");
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritDoc}.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('seeds_layouts.manager'),
      $container->get('plugin.manager.layout_field'),
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Initialization.
    $configuration = $this->getConfiguration();

    if (!isset($configuration['layout_fields'])) {
      $configuration['layout_fields'] = [];
    }

    $regions = $this->getPluginDefinition()->getRegions();
    $regions_count = count($regions);
    $sizes = [
      'desktop' => $this->t("Desktop"),
      'tablet' => $this->t("Tablet"),
      'mobile' => $this->t("Mobile"),
    ];

    // Custom Fields.
    $layout_fields = $this->seedsLayoutConfig->get('layout_fields');
    /* @todo Add sortable drag and drop table */
    $form['layout_fields'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];
    foreach ($layout_fields as $key => $layout_field) {
      $config = $layout_field['custom_field'];
      $description = $config['description'];
      // Ignore _none plugins if exist.
      if ($description['type'] == '_none') {
        continue;
      }
      // Create custom fields instances and build their forms.
      $default_values = @$configuration['layout_fields'][$key];
      $field_instance = $this->layoutFieldManager->createInstance($description['type'], NestedArray::mergeDeepArray([$config, $default_values]));
      // Initialize an empty div subform.
      $empty_form = ['#type' => 'container'];
      $subform_state = SubformState::createForSubform($empty_form, $form, $form_state);
      // Call the 'build' method from the 'LayoutField' instance.
      $field_form = $field_instance->build($empty_form, $subform_state);
      // Set the form weight.
      $field_form['#weight'] = $layout_field['weight'];

      // @todo Determine if this line is important.
      $field_form['type'] = [
        '#type' => 'value',
        '#value' => $description['type'],
      ];

      // Render the custom fields.
      $form['layout_fields'][$key] = $field_form;
    }

    // Define the three select sizes.
    $mobile = $tablet = $desktop = [
      '#type' => 'radios',
      '#options' => [],
      '#access' => FALSE,
    ];

    $columns = $this->seedsColumnsConfig->get();
    foreach ($columns as $column) {
      // Check if this column attribute belongs to this region.
      if ($column['description']['type'] != $this->getPluginDefinition()->getThemeHook()) {
        continue;
      }

      // Get the column info.
      $size = $column['description']['size'];
      $label = $column['description']['label'];
      $classes = implode(',', $column['classes']);
      $image = $column['image_url'];

      // Or simply add it to its appropiate size element.
      ${$size}['#options'][$classes] = '<img class="layout-icon" src="' . $image . '" />';
    }

    // Loop through the $sizes and build them.
    foreach ($sizes as $size => $label) {
      ${$size}['#title'] = $label;
      ${$size}['#default_value'] = $configuration['columns'][$size];

      if (${$size}['#options']) {
        // If the size has options, we reveal it.
        ${$size}['#access'] = TRUE;
      }
    }

    // A container to wrap the size elements.
    $form['columns'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      'desktop' => $desktop,
      'tablet' => $tablet,
      'mobile' => $mobile,
    ];

    // A checkbox to reverse the order of the columns.
    if ($regions_count > 1) {
      $form['reverse'] = [
        '#type' => 'checkbox',
        '#title' => $this->t("Reverse"),
        '#default_value' => $configuration['reverse'],
      ];
    }

    // Container checkbox (If the current framework supports containers).
    if ($this->seedsLayoutConfig->get('framework_has_container')) {
      $form['container'] = [
        '#type' => 'checkbox',
        // We simply flip the title and values if the current user
        // doesn't user advanced layouts.
        '#title' => $this->userFriendly ? t("Full Width") : t("Contianer"),
        '#default_value' => $this->userFriendly ? !$configuration['container'] : $configuration['container'],
      ];
    }

    // Advanced settings only for users who have the permission
    // to use advanced layouts.
    $form['advanced'] = [
      '#type' => 'details',
      '#title' => t("Advanced"),
      '#tree' => TRUE,
    ];

    // The region attributes field.
    foreach ($regions as $id => $region) {
      $region_saved_attributes = isset($configuration['advanced'][$id . '_attributes']) ? $configuration['advanced'][$id . '_attributes'] : "";
      $region_default_attributes = $regions_count == 1 ? $this->seedsLayoutConfig->get('one_column_attributes') : "";
      $form['advanced'][$id . "_attributes"] = [
        '#type' => 'textfield',
        '#title' => $this->t("%region Attributes", ['%region' => $region['label']]),
        '#default_value' => $region_saved_attributes ? $region_saved_attributes : $region_default_attributes,
      ];
    }

    // The section attributes field.
    $form['advanced']['section_attributes'] = [
      '#type' => 'textfield',
      '#title' => $this->t("%region Attributes", ['%region' => $this->t("Section")]),
      '#default_value' => $configuration['advanced']['section_attributes'],
    ];

    $override_columns_parent_attributes = $configuration['advanced']['columns_parent_attributes'];
    $form['advanced']['columns_parent_attributes'] = [
      '#type' => 'textfield',
      '#title' => $this->t("%region Attributes", ['%region' => $this->t("Column Parents")]),
      '#default_value' => $override_columns_parent_attributes ? $override_columns_parent_attributes : $this->seedsLayoutConfig->get('columns_parent_attributes'),
      '#description' => $this->t('Used as: @example', ['@example' => '"class|example-class,data-example|example-value"']),
    ];

    // Attach the library.
    $form['#attached']['library'][] = 'seeds_layouts/layout_settings';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $regions = $this->getPluginDefinition()->getRegions();

    // Set columns width.
    $columns = $values['columns'];
    // Loop through the sizes elements.
    foreach ($regions as $region => $label) {
      foreach ($columns as $column) {
        if (isset($column) && $column != "_none") {
          $classes = $this->seedsLayoutsManager->sortClassesPerRegion($column, $regions);
          $values[$region . '_column'][] = $classes[$region];
        }
      }
    }

    // Set advanced attributes.
    foreach ($regions as $key => $name) {
      $values[$key . "_attributes"] = Xss::filter($values['advanced'][$key . "_attributes"]);
    }
    $values['section_attributes'] = Xss::filter($values['advanced']["section_attributes"]);
    $values['columns_parent_attributes'] = Xss::filter($values['advanced']["columns_parent_attributes"]);

    // Get attributes from custom fields.
    $field_config = $this->seedsLayoutConfig->get('layout_fields');
    $layout_fields_attributes = [];

    if (isset($values['layout_fields'])) {
      foreach ($values['layout_fields'] as $key => $config) {
        $field_instance = $this->layoutFieldManager->createInstance($config['type'], NestedArray::mergeDeepArray([$config, $field_config[$key]['custom_field']]));
        $field_attributes = $field_instance->getAttributes();
        $layout_fields_attributes = NestedArray::mergeDeepArray([$field_attributes, $layout_fields_attributes]);
      }
    }

    // Finalization.
    $values['layout_fields_attributes'] = $layout_fields_attributes;
    $values['container'] = $this->userFriendly ? !$values['container'] : $values['container'];
    $this->setConfiguration($values);
  }

}
