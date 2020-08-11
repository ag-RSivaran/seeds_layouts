<?php

namespace Drupal\seeds_layouts\Form;

use Drupal\Component\Uuid\Php;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\seeds_layouts\Plugin\LayoutFieldManager;
use Drupal\seeds_layouts\SeedsLayoutsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SeedsLayoutsSettingsForm.
 */
class SeedsLayoutsConfigForm extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Layout field manager.
   *
   * @var \Drupal\seeds_layouts\Plugin\LayoutFieldManager
   */
  protected $layoutFieldManager;

  /**
   * Seeds layouts manager.
   *
   * @var \Drupal\seeds_layouts\SeedsLayoutsManager
   */
  protected $seedsLayoutsManager;

  /**
   * Form Builder instance.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * Uuid instance.
   *
   * @var \Drupal\Component\Uuid\Php
   */
  protected $uuid;

  /**
   * Constructs a new SeedsLayoutsSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\seeds_layouts\Plugin\LayoutFieldManager $layout_field_manager
   *   Layout field plugin manager.
   * @param \Drupal\seeds_layouts\SeedsLayoutsManager $seeds_layouts_manager
   *   Seeds layouts manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManager $entity_type_manager,
    LayoutFieldManager $layout_field_manager,
    SeedsLayoutsManager $seeds_layouts_manager,
    FormBuilder $form_builder,
    Php $uuid
  ) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->layoutFieldManager = $layout_field_manager;
    $this->seedsLayoutsManager = $seeds_layouts_manager;
    $this->formBuilder = $form_builder;
    $this->uuid = $uuid;
  }

  /**
   * {@inheritDoc}.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.layout_field'),
      $container->get('seeds_layouts.manager'),
      $container->get('form_builder'),
      $container->get('uuid')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'seeds_layouts.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'seeds_layouts_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Include custom css for the form.
    $form['#attached']['library'][] = 'seeds_layouts/module_config';
    $form['#attributes']['class'][] = 'seeds-layouts-form';
    $form['#attributes']['class'][] = 'seeds-layouts-config';

    // Form Initialization #.
    $config = $this->config('seeds_layouts.config');
    $form = parent::buildForm($form, $form_state);

    // Initialization for the first build.
    if (!$this->initialized($form_state)) {
      $form_state->set('layout_fields', $config->get('layout_fields'));
    }

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General Framework Settings'),
      '#open' => TRUE,
      '#tree' => FALSE,
    ];

    // Default Columns Parent Attributes.
    $form['general']['columns_parent_attributes'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Default Columns Parent Attributes"),
      '#default_value' => $config->get('columns_parent_attributes'),
    ];

    // Default One Column attributes.
    $form['general']['one_column_attributes'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Default One Columns Attributes"),
      '#default_value' => $config->get('one_column_attributes'),
      '#description' => $this->t('Used as: @example', ['@example' => '"class|example-class,data-example|example-value"']),
    ];

    // Has Contrainer.
    $form['general']['framework_has_container'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Framework has Container?"),
      '#default_value' => $config->get('framework_has_container'),
    ];

    // Container Class.
    $form['general']['container_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Default Container Class"),
      '#default_value' => $config->get('container_class'),
      '#states' => [
        'visible' => [
          'input[name="framework_has_container"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Custom Fields.
    $options = $this->layoutFieldManager->getOptions();
    $layout_fields_wrapper = [
      '#type' => 'table',
      '#title' => $this->t("Custom Layout Fields"),
      '#header' => [
        $this->t('Custom Fields'),
        $this->t("Remove"),
        $this->t('Weight'),
      ],
      '#empty' => $this->t('Add new custom fields'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'table-sort-weight',
        ],
      ],
      '#tree' => TRUE,
    ];

    // Loop throught all the current fields in the form to build them.
    foreach ($this->getLayoutFields($form_state) as $uuid => $values) {
      // Setup field config.
      if (!empty($form_state->getUserInput()['layout_fields'][$uuid])) {
        $layout_field = $form_state->getUserInput()['layout_fields'][$uuid];
      }
      else {
        $layout_field = @$config->get('layout_fields')[$uuid];
      }

      $layout_field_config = $layout_field['custom_field'];
      $description = $layout_field_config['description'];

      // Build a container for the field.
      $layout_field = [
        '#type' => 'container',
        '#attributes' => [
          'id' => $uuid,
          'style' => ['clear:both;'],
        ],
        '#tree' => TRUE,
      ];

      $layout_field['description'] = [
        '#type' => 'container',
        '#tree' => TRUE,
        '#attributes' => [
          'class' => ['form--inline', 'clearfix'],
        ],
      ];

      // Build the 'LayoutField' plugin to select.
      $layout_field['description']['type'] = [
        '#type' => 'select',
        '#title' => 'Type',
        '#required' => TRUE,
        '#options' => $options,
        '#default_value' => $description['type'],
        '#ajax' => [
          'callback' => '::updateLayoutFields',
          'event' => 'change',
          'wrapper' => 'layout_fields',
        ],
      ];

      // Create an instance of the plugin and call the 'build' function
      // in that instance.
      if ($type = $description['type']) {
        /** @var \Drupal\seeds_layouts\Plugin\LayoutF ieldInterface $layout_field_instance */
        $layout_field_instance = $this->layoutFieldManager->createInstance($type, $layout_field_config);
        $layout_field['description']['label'] = [
          '#type' => 'textfield',
          '#title' => $this->t("Label"),
          '#default_value' => $description['label'],
          '#required' => TRUE,
        ];
        $layout_field = $layout_field_instance->buildConfigurationForm($layout_field, $form_state);
      }

      $weight = isset($layout_field['weight']) ? $layout_field['weight'] : 0;
      // Add the field to the wrapper.
      $layout_fields_wrapper[$uuid] = [
        '#weight' => $weight,
        'custom_field' => $layout_field,
        'remove' => [
          '#type' => 'submit',
          '#name' => $uuid,
          '#value' => $this->t("Remove Field"),
          '#submit' => ['::removeLayoutField'],
          '#limit_validation_errors' => [],
          '#attributes' => [
            'class' => ['bg-danger', 'seeds-layouts-remove'],
          ],
          '#ajax' => [
            'callback' => '::updateLayoutFields',
            'wrapper' => 'layout_fields',
            'method' => 'replace',
          ],
        ],
        'weight' => [
          '#type' => 'weight',
          '#title' => $this->t('Weight'),
          '#title_display' => 'invisible',
          '#default_value' => $weight,
          '#attributes' => ['class' => ['table-sort-weight']],
        ],
        '#attributes' => [
          'class' => ['draggable'],
        ],
      ];
    }

    // Finally build the fields wrapper into the form.
    $form['layout_fields_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom Fields'),
      '#open' => TRUE,
      '#attributes' => ['id' => 'layout_fields'],
      '#tree' => FALSE,
      'layout_fields' => $layout_fields_wrapper,
    ];

    // Add a button to add layout fields.
    $form['layout_fields_add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Field'),
      '#submit' => ['::addLayoutField'],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => '::updateLayoutFields',
        'wrapper' => 'layout_fields',
        'method' => 'replace',
      ],
    ];
    return $form;
  }

  /**
   * Returns the current layout fields in the form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The layout fields in the form state storage.
   */
  private function getLayoutFields(FormStateInterface $form_state) {
    $layout_fields = $form_state->get('layout_fields');
    return $layout_fields ? $layout_fields : [];
  }

  /**
   * Checks if the form was built the first time. And set it to be initialized.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if the form is initialized.
   */
  private function initialized(FormStateInterface $form_state) {
    $initialized = $form_state->get('initialized') === TRUE;
    if (!$initialized) {
      $form_state->set('initialized', TRUE);
    }
    return $initialized;
  }

  /**
   * Update the layout fields container using ajax.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function updateLayoutFields(array &$form, FormStateInterface $form_state) {
    return $form['layout_fields_wrapper'];
  }

  /**
   * Adds a layout field to the form_state storage.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addLayoutField(array &$form, FormStateInterface $form_state) {
    $layout_fields = $form_state->get('layout_fields');
    $layout_fields = $layout_fields ? $layout_fields : [];
    $layout_fields[$this->uuid->generate()] = [];
    $form_state->set('layout_fields', $layout_fields);
    $form_state->setRebuild();
  }

  /**
   * Removes a layout field to the form_state storage.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function removeLayoutField(array &$form, FormStateInterface $form_state) {
    $layout_fields = $this->getLayoutFields($form_state);
    $uuid = $form_state->getTriggeringElement()['#name'];
    unset($layout_fields[$uuid]);
    $form_state->set('layout_fields', $layout_fields);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('seeds_layouts.config');
    $values = $form_state->getValues();

    $layout_fields = $values['layout_fields'] ? $values['layout_fields'] : [];

    $config->set('columns_parent_attributes', $values['columns_parent_attributes']);
    $config->set('framework_has_container', $values['framework_has_container']);
    $config->set('container_class', $values['container_class']);
    $config->set('one_column_attributes', $values['one_column_attributes']);
    $config->set('layout_fields', $layout_fields);
    $config->save();
  }

}
