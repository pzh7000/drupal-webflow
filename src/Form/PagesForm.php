<?php

namespace Drupal\webflow\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webflow\WebflowApi;
use GuzzleHttp\Exception\ClientException;

/**
 * Configure webflow settings for this site.
 */
class PagesForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webflow_pages';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['webflow.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (empty($this->config('webflow.settings')->get('api_key'))) {
      \Drupal::messenger()->addWarning('Please add your API on the settings page first.');
    }

    $form['redirect_header'] = [
      '#prefix' => '<div class="redirect-header"><p><strong>Create a new redirect</strong></p><p>Create redirects to serve a made-in-Webflow page in place of a Drupal page</p>',
      '#suffix' => '</div>'
    ];

    $header = [
      'drupal_path' => $this->t('Drupal Path'),
      'webflow_page' => $this->t('Webflow Page'),
      'remove' => $this->t('Remove')
    ];

    $form['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#tree' => TRUE,
      '#prefix' => '<div id="table-wrapper">',
      '#suffix' => '</div>'
    ];

    $count = $form_state->get('mapping_count');
    if (is_null($count)) {
      $mappings = $this->config('webflow.settings')->get('path_mappings');
      $count = count($mappings);
      $form_state->set('mapping_count', $count);
    }

    for ($delta = 0; $delta <= $count; $delta++) {
      if (!isset($form['table'][$delta])) {
        $form['table'][$delta] = $this->buildRow($delta);
      }
    }

    $form['add_row'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another'),
      '#submit' => ['::addRowSubmit'],
      '#ajax' => [
        'callback'=> '::addRowCallback',
        'wrapper' => 'table-wrapper',
        'effect' => 'fade'
      ]
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   * @TODO: Update logic here.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // @TODO: Validate all rows have values for both drupal_path and webflow_page

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @TODO: Check for empty values and don't set them to path_mappings.
    $rows = $form_state->getValue('table');
    $this->config('webflow.settings')
      ->set('path_mappings', $rows)
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Additional form submit handler
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function addRowSubmit(array &$form, FormStateInterface &$form_state) {
    $count = $form_state->get('mapping_count') + 1;
    $form_state->set('mapping_count', $count);
    $form_state->setRebuild(TRUE);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function addRowCallback(array &$form, FormStateInterface &$form_state) {
    return $form['table'];
  }

  public function removeRowCallback(array &$form, FormStateInterface &$form_state) {
    $count = $form_state->get('mapping_count') - 1;
    $form_state->set('mapping_count', $count);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Helper method to build options of Static Pages from Webflow.
   *
   * @return array
   *   Associative array of options
   */
  private function buildStaticPageOptions() {
    $options = [];
    if (!is_null($this->config('webflow.settings')->get('api_key'))) {
      // @TODO: DI this service
      /** @var WebflowApi $webflow */
      $webflow = \Drupal::service('webflow.webflow_api');
      try {
        $static_pages = $webflow->getStaticPages();
      } catch (ClientException $e) {
        \Drupal::messenger()->addError("The API key you used is invalid: failed to list sites");
      }

      foreach ($static_pages as $page) {
        $options[$page] = $page === '/index.html' ? 'Home' : $page;
      }
    }

    return $options;
  }

  private function buildRow(int $delta) {
    $mappings = $this->config('webflow.settings')->get('path_mappings');
    $mapping = [];
    if (isset($mappings[$delta])) {
      $mapping = $mappings[$delta];
    }

    $row['drupal_path'] = [
      '#title' => $this->t('Drupal Path'),
      '#title_display' => 'invisible',
      '#type' => 'textfield',
      '#default_value' => $mapping['drupal_path'] ?? '',
    ];

    $row['webflow_page'] = [
      '#title' => $this->t('Webflow Page'),
      '#title_display' => 'invisible',
      '#type' =>  'select',
      '#options' => $this->buildStaticPageOptions(),
      '#default_value' => $mapping['webflow_page'] ?? '',
    ];

    $row['remove'] = [
      '#type' => 'checkbox',
      '#default_value' => FALSE,
      '#title' => $this->t('Remove'),
      '#title_display' => 'invisible',
      '#ajax' => [
        'callback' => '::removeRowCallback',
        'wrapper' => 'table-wrapper',
        'effect' => 'fade',
        'progress' => 'none',
      ],
      '#delta' => $delta,
      '#parents' => ['remove_mappings', $delta],
      '#remove' => TRUE,
    ];

    return $row;
  }

}
