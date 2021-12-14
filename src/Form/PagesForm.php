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
    $form['redirect_header'] = [
      '#prefix' => '<div class="redirect-header"><p><strong>Create a new redirect</strong></p><p>Create redirects to serve a made-in-Webflow page in place of a Drupal page</p>',
      '#suffix' => '</div>'
    ];

    $header = [
      $this->t('Drupal Path'),
      $this->t('Webflow Page'),
    ];

    $form['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#tree' => TRUE
    ];

    $mappings = $this->config('webflow.settings')->get('path_mappings');
    $counter = 0;
    // @TODO: Add logic if there are no mappings
    foreach ($mappings as $mapping) {
      $row['drupal_path'] = [
        '#type' => 'textfield',
        '#default_value' => $mapping['drupal_path'] ?? '',
        '#required' => true,
      ];
      $row['webflow_page'] = [
        '#type' =>  'select',
        '#options' => $this->buildStaticPageOptions(),
        '#default_value' => $mapping['webflow_page'] ?? NULL,
      ];

      $form['table'][$counter] = $row;
      $counter++;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
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
    parent::submitForm($form, $form_state);
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

}
