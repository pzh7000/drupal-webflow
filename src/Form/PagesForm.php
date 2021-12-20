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


    if ($form_state->getValues()) {
      $this->processMappings($form, $form_state);
    }

    $mappings = $this->config('webflow.settings')->get('path_mappings');
    foreach ($mappings as $delta => $mapping) {
      $form['table'][$delta] = $this->buildRow($delta, $mapping);
    }

    $form['add_row'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another'),
      '#submit' => ['::addRowSubmit'],
      '#ajax' => [
        'callback'=> '::ajaxCallback',
        'wrapper' => 'table-wrapper',
        'effect' => 'fade'
      ]
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function processMappings(array $form, FormStateInterface $form_state) {
    $mappings = $this->config('webflow.settings')->get('path_mappings');

    // Remove mappings
    foreach (array_keys(array_filter($form_state->getValue('remove_mappings', []))) as $delta) {
      unset($mappings[$delta]);
    }

    $this->config('webflow.settings')
      ->set('path_mappings', $mappings)
      ->save();
  }

  /**
   * {@inheritdoc}
   * @TODO: validte routes do not collide with existing drupal routes
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // @TODO: Loop over each of the table rows and ensure that both the drupal_path and webflow_page have values before saving.
    if ($form_state->getValue('drupal_path') === '') {
      $form_state->setErrorByName('drupal_path', $this->t('Please supply a valid Drupal path'));
    }
    if ($form_state->getValue('webflow_page') === '') {
      $form_state->setErrorByName('webflow_page', $this->t('Please supply a valid Webflow page'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $rows = $form_state->getValue('table');
    $this->config('webflow.settings')
      ->set('path_mappings', $rows)
      ->save();
    // @TODO: Rebuild routing as a new path has been created.
    parent::submitForm($form, $form_state);
  }

  /**
   * Additional form submit handler for adding a blank new row.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function addRowSubmit(array &$form, FormStateInterface &$form_state) {
    $mappings = $this->config('webflow.settings')->get('path_mappings');
    $mappings[] = [
      'drupal_path' => '',
      'webflow_page' => ''
    ];
    $this->config('webflow.settings')->set('path_mappings', $mappings);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Ajax callback for form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function ajaxCallback(array &$form, FormStateInterface &$form_state) {
    return $form['table'];
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

  /**
   * @param int $delta
   *
   * @return array
   */
  private function buildRow(int $delta, array $mapping) {

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
        'callback' => '::ajaxCallback',
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
