<?php

namespace Drupal\seeds_layouts\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\seeds_layouts\SeedsLayoutsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FrameworkImportForm.
 */
class FrameworkImportForm extends FormBase {

  /**
   * Drupal\seeds_layouts\SeedsLayoutsManager definition.
   *
   * @var \Drupal\seeds_layouts\SeedsLayoutsManager
   */
  protected $seedsLayoutsManager;

  /**
   * Constructs a new FrameworkImportForm object.
   */
  public function __construct(
    SeedsLayoutsManager $seeds_layouts_manager,
    MessengerInterface $messenger
  ) {
    $this->seedsLayoutsManager = $seeds_layouts_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('seeds_layouts.manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'framework_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['framework'] = [
      '#type' => 'select',
      '#title' => $this->t('Framework'),
      '#options' => $this->seedsLayoutsManager->getSupportedFrameworks(),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Import the framework.
    $framework_id = $form_state->getValue('framework');
    $this->seedsLayoutsManager->importFramework($framework_id);
    $label = $this->seedsLayoutsManager->getSupportedFrameworks()[$framework_id];
    $this->messenger->addMessage($this->t("Framework %label has been successfully imported.", ['%label' => $label]));
    $this->redirect('seeds_layouts.columns')->send();
  }

}
