<?php

namespace Drupal\seeds_layouts\Plugin\LayoutField;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\seeds_layouts\Plugin\LayoutFieldBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'background_image' field.
 *
 * @LayoutField(
 *   id = "background_image",
 *   label = @Translation("Background Image")
 * )
 */
class BackgroundImageField extends LayoutFieldBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManagar;

  /**
   * Constructs a new 'background_image' plugin.
   *
   * @param array $configuration
   *   The configuration.
   * @param $plugin_id
   *   The plugin id
   * @param $plugin_definition
   *   The plugin definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @return \Drupal\seeds_layouts\Plugin\LayoutField\BackgroundImageField
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManagar = $entity_type_manager;
  }

  /**
   * {@inheritDoc}.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritDoc}.
   */
  public function getAttributes() {
    $attributes = [];
    /** @var \Drupal\file\FileInterface $file */
    $file_value = !empty($this->getConfiguration('file')) ? $this->getConfiguration('file') : [];
    $file = $this->entityTypeManagar->getStorage('file')->load(reset($file_value));
    $uri = "";

    if ($file) {
      $uri = $file->createFileUrl();
    }

    $attributes['style'] = "background-image: url($uri);";

    if ((bool) $this->getConfiguration('parallax')) {
      $attributes['class'][] = 'seeds-layouts-parallax';
    }

    if ((bool) $this->getConfiguration('repeat')) {
      $attributes['class'][] = 'repeat';
    }

    return $attributes;
  }

  /**
   *
   */
  public function getLibraries() {
    return ['seeds_layouts/parallax'];
  }

  /**
   * {@inheritDoc}.
   */
  public function getConfiguration($key = NULL) {
    $wrapper = parent::getConfiguration('wrapper');
    if ($key) {
      return isset($wrapper[$key]) ? $wrapper[$key] : NULL;
    }

    return $wrapper;
  }

  /**
   * {@inheritDoc}.
   */
  public function build(array $form, FormStateInterface $form_state) {

    $form['wrapper'] = [
      '#type' => 'details',
      '#title' => $this->getLabel(),
      '#tree' => TRUE,
    ];

    $form['wrapper']['file'] = [
      '#type' => 'managed_file',
      '#upload_location' => 'public://seeds_layouts',
      '#title' => t("Background Image"),
      '#default_value' => $this->getConfiguration('file'),
    ];

    $form['wrapper']['parallax'] = [
      '#type' => 'checkbox',
      '#title' => t("Parallax"),
      '#default_value' => $this->getConfiguration('parallax'),
    ];

    $form['wrapper']['repeat'] = [
      '#type' => 'checkbox',
      '#title' => t("Repeat Background"),
      '#default_value' => $this->getConfiguration('repeat'),
    ];

    return $form;
  }

}
