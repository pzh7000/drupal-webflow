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
    return 'webflow_settings';
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
    if (!is_null($this->config('webflow.settings')->get('api-key'))) {
      /** @var WebflowApi $webflow */
      $webflow = \Drupal::service('webflow.webflow_api');
      try {
        $options = $webflow->getStaticPages();
      } catch (ClientException $e) {
        \Drupal::messenger()->addError("The API key you used is invalid: failed to list sites");
      }
    }

    $form['redirect_header'] = [
      '#prefix' => '<div class="redirect-header"><p><strong>Create a new redirect</strong></p><p>Create redirects to serve a made-in-Webflow page in place of a Drupal page</p>',
      '#suffix' => '</div>'
    ];

    $header = [
      'col1' => t('Drupal Path'),
      'col2' => t('Webflow Page'),
    ];

    $form['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#tree' => TRUE
    ];

    // @TODO: get stored pages from config
    $pages = [
      '/contact' => '/webflow-contact',
      '/some-other-page' => '/webflow-other'
    ];
    $counter = 0;
    foreach ($pages as $drupal_url => $webflow_url) {
      $row['drupal_path'] = [
        '#type' => 'textfield',
        '#default_value' => $drupal_url,
        '#required' => true,
      ];
      $row['webflow_page'] = [
        '#type' =>  'select',
        '#options' => $options
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
    /** @var WebflowApi $webflow */
    $this->config('webflow.settings')
      ->set('drupal-path', $form_state->getValue('drupal_path'))
      ->set('webflow-page', $form_state->getValue('webflow_page'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
