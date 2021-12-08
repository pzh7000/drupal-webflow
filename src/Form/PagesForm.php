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
    $form['redirect_header'] = [
      '#prefix' => '<div class="redirect-header"><p><strong>Create a new redirect</strong></p><p>Create redirects to serve a made-in-Webflow page in place of a Drupal page</p>',
      '#suffix' => '</div>'
    ];

    $form['new_redirect_table'] = [
      '#prefix' => '<table>',
      '#suffix' => '</table>'
    ];

    $form['new_redirect_table']['table_row'] = [
      '#prefix' => '<tr>',
      '#suffix' => '</tr>'
    ];

    $form['new_redirect_table']['table_row']['drupal_path'] = [
      '#prefix' => '<td>',
      '#suffix' => '</td>',
      '#type' => 'textfield',
      '#title' => $this->t('Drupal Path'),
      '#default_value' => $this->config('webflow.settings')->get('drupal-path'),
      '#required' => true,
    ];

    $form['new_redirect_table']['table_row']['webflow_page'] = [
      '#prefix' => '<td>',
      '#suffix' => '</td>',
      '#type' => 'textfield',
      '#title' => $this->t('Webflow Page'),
      '#default_value' => $this->config('webflow.settings')->get('webflow-page'),
      '#required' => true,
    ];

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
