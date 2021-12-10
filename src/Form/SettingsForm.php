<?php

namespace Drupal\webflow\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webflow\WebflowApi;
use GuzzleHttp\Exception\ClientException;

/**
 * Configure webflow settings for this site.
 */
class SettingsForm extends ConfigFormBase {

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
    $form['webflow_api'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Add Webflow API Key'),
      '#default_value' => $this->config('webflow.settings')->get('api-key'),
      '#required' => true,
    ];
    if (!is_null($this->config('webflow.settings')->get('api-key'))) {
      /** @var WebflowApi $webflow */
      $webflow = \Drupal::service('webflow.webflow_api');
      try {
        $response = $webflow->getSites();
      } catch (ClientException $e) {
        \Drupal::messenger()->addError("The API key you used is invalid: failed to list sites");
      }

      if (is_array($response)) {
        $preview_url = $response[0]->previewUrl;
        $form['preview'] = [
          '#markup' => '<img src="' . $preview_url . '"/>'
        ];
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('webflow_api') === '') {
      $form_state->setErrorByName('webflow_api', $this->t('Please supply a valid Webflow API key'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var WebflowApi $webflow */
    $this->config('webflow.settings')
      ->set('api-key', $form_state->getValue('webflow_api'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
