<?php

namespace Drupal\seeds_layouts\Form;

use Drupal\Component\Uuid\Php;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\seeds_layouts\SeedsLayoutsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ColumnsForm.
 */
class ColumnsForm extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

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
   * Constructs a new ColumnsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\seeds_layouts\Plugin\ColumnFieldManager $column_field_manager
   *   Column field plugin manager.
   * @param \Drupal\seeds_layouts\SeedsLayoutsManager $seeds_layouts_manager
   *   Seeds layouts manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManager $entity_type_manager,
    SeedsLayoutsManager $seeds_layouts_manager,
    FormBuilder $form_builder,
    Php $uuid
  ) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
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
      'seeds_layouts.columns',
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

    // Include custom css style for form.
    $form['#attached']['library'][] = 'seeds_layouts/module_config';
    $form['#attributes']['class'][] = 'seeds-layouts-form';
    $form['#attributes']['class'][] = 'seeds-layouts-columns';

    // Form Initialization #.
    $config = $this->config('seeds_layouts.columns');
    $form = parent::buildForm($form, $form_state);

    // Initialization for the first build.
    if (!$this->initialized($form_state)) {
      $form_state->set('columns', $config->getRawData());
    }
    // Custom Fields #.
    $columns_wrapper = [
      '#type' => 'container',
      '#title' => $this->t("Custom Column Fields"),
      '#attributes' => ['id' => 'columns'],
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    // Loop throught all the current fields in the form to build them.
    foreach ($this->getColumnFields($form_state) as $uuid => $values) {
      // Setup field config.
      $column_field_config = !empty($form_state->getUserInput()['columns'][$uuid]['column_field']) ?
        $form_state->getUserInput()['columns'][$uuid]['column_field'] :
        $config->get($uuid);

      // Build a container for the field.
      $column_field = [
        '#type' => 'container',
        '#attributes' => [
          'id' => $uuid,
        ],
        '#title' => $values['name'],
        '#tree' => TRUE,
        '#open' => TRUE,
      ];

      $column_field['description'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['form--inline', 'clearfix'],
        ],
      ];

      // Build the 'ColumnField' plugin to select.
      $column_field['description']['type'] = [
        '#type' => 'select',
        '#title' => 'Type',
        '#required' => TRUE,
        '#options' => [
          'seeds_2col' => t("Two Columns"),
          'seeds_3col' => t("Three Columns"),
        ],
        '#default_value' => $column_field_config['description']['type'],
        '#ajax' => [
          'callback' => '::updateColumnFields',
          'event' => 'change',
          'wrapper' => 'columns',
        ],
      ];

      // Default label.
      if (empty($column_field_config['description']['label'])) {
        $column_field_config['description']['label'] = $this->t('New Column Style');
      }

      // Put the size beside the label for better user experience.
      if (empty($column_field_config['description']['size'])) {
        $size = $this->t('Size');
      }
      else {
        $size = $this->getSizes()[$column_field_config['description']['size']];
      }

      $column_field['#type'] = 'details';
      $column_field['#open'] = FALSE;
      $column_field['#title'] = "$size | {$column_field_config['description']['label']}";

      // Create an instance of the plugin and call the 'build' function
      // in that instance.
      if ($column_field_config['description']['type']) {
        $this->buildColumnForm($column_field, $column_field_config);
      }

      // Add the field to the wrapper.
      $columns_wrapper[$uuid] = [
        '#type' => 'container',
        '#tree' => TRUE,
        '#attributes' => [
          'class' => ['seeds-layouts-row'],
        ],
        'column_field' => $column_field,
        'remove' => [
          '#type' => 'submit',
          '#name' => $uuid,
          '#value' => $this->t("Remove Style"),
          '#submit' => ['::removeColumnField'],
          '#limit_validation_errors' => [],
          '#attributes' => [
            'class' => ['seeds-layouts-remove'],
          ],
          '#ajax' => [
            'callback' => '::updateColumnFields',
            'wrapper' => 'columns',
            'method' => 'replace',
          ],
        ],
      ];
    }

    // Finally build the fields wrapper into the form.
    $form['columns'] = $columns_wrapper;

    // Add a button to add Column fields.
    $form['columns_add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Style'),
      '#submit' => ['::addColumnField'],
      '#limit_validation_errors' => [],
      '#attributes' => [
        'class' => ['form-button'],
      ],
      '#ajax' => [
        'callback' => '::updateColumnFields',
        'wrapper' => 'columns',
        'method' => 'replace',
      ],
    ];
    return $form;
  }

  /**
   * Returns the current column fields in the form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The column fields in the form state storage.
   */
  private function getColumnFields(FormStateInterface $form_state) {
    $columns = $form_state->get('columns');
    return $columns ? $columns : [];
  }

  /**
   * Gets the available sizes.
   *
   * @return array
   */
  private function getSizes() {
    return [
      'desktop' => $this->t('Desktop'),
      'tablet' => $this->t('Tablet'),
      'mobile' => $this->t('Mobile'),
    ];
  }

  /**
   * Build column form.
   */
  private function buildColumnForm(array &$form, array $column_config) {
    $type = $column_config['description']['type'];

    $form['description']['label'] = [
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#size' => 35,
      '#required' => TRUE,
      '#default_value' => $column_config['description']['label'],
    ];
    $form['description']['size'] = [
      '#type' => 'select',
      '#title' => t('Size'),
      '#options' => $this->getSizes(),
      '#required' => TRUE,
      '#default_value' => $column_config['description']['size'],
    ];

    $form['image_url'] = [
      '#type' => 'textfield',
      '#title' => t('Image URL'),
      '#required' => TRUE,
      '#default_value' => $column_config['image_url'],
    ];

    $form['classes'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['form--inline', 'clearfix'],
      ],
      '#tree' => TRUE,
    ];

    $form['classes']['left'] = [
      '#type' => 'textfield',
      '#title' => t('Left'),
      '#size' => 20,
      '#required' => TRUE,
      '#default_value' => $column_config['classes']['left'],
    ];

    if ($type == "seeds_3col") {
      $form['classes']['middle'] = [
        '#type' => 'textfield',
        '#title' => t('Middle'),
        '#size' => 20,
        '#required' => TRUE,
        '#default_value' => $column_config['classes']['middle'],
      ];
    }

    $form['classes']['right'] = [
      '#type' => 'textfield',
      '#title' => t('Right'),
      '#size' => 20,
      '#required' => TRUE,
      '#default_value' => $column_config['classes']['right'],
    ];

    return $form;
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
   * Update the column fields container using ajax.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function updateColumnFields(array &$form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    $uuid = $element['#parents'][1];
    $form['columns'][$uuid]['column_field']['#open'] = TRUE;
    return $form['columns'];
  }

  /**
   * Adds a column field to the form_state storage.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addColumnField(array &$form, FormStateInterface $form_state) {
    $columns = $form_state->get('columns');
    $columns = $columns ? $columns : [];
    $columns[$this->uuid->generate()] = [];
    $form_state->set('columns', $columns);
    $form_state->setRebuild();
  }

  /**
   * Removes a column field to the form_state storage.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function removeColumnField(array &$form, FormStateInterface $form_state) {
    $columns = $this->getColumnFields($form_state);
    $uuid = $form_state->getTriggeringElement()['#name'];
    unset($columns[$uuid]);
    $form_state->set('columns', $columns);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('seeds_layouts.columns');
    $values = $form_state->getValues();
    $columns = $values['columns'] ? $values['columns'] : [];
    $styles = [];
    foreach ($columns as $uuid => $col) {
      $styles[$uuid] = $col['column_field'];
    }

    $config->setData($styles);
    $config->save();
  }

}
