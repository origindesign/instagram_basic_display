<?php

/**
 * @file
 * Contains \Drupal\instagram_basic_display\Form\settingsForm.
 */

namespace Drupal\instagram_basic_display\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\instagram_basic_display\Controller\InstagramBasicDisplayController;
use Drupal\instagram_basic_display\InstagramBasicDisplayApi;
use Drupal\Core\Url;


/**
 * Instagram form.
 */
class settingsForm extends ConfigFormBase {


  /**
   * @var InstagramController
   */
  protected $controller;
  protected $instagramBasicDisplayApi;


  /**
   * InstagramSettingsForm constructor.
   *
   * @param InstagramController $controller
   */
  public function __construct(InstagramBasicDisplayController $controller, InstagramBasicDisplayApi $instagramBasicDisplayApi) {
    $this->controller = $controller;
    $this->instagramBasicDisplayApi = $instagramBasicDisplayApi;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('instagram_basic_display.instagram_controller'),
      $container->get('instagram_basic_display.instagram_basic_display_api')
    );
  }


  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'instagram_basic_display.settings',
    ];
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'instagram_basic_display_settings_form';
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('instagram_basic_display.settings');

    // If config has been set
    if($config->get('app_key') != '' && $config->get('app_secret') != ''){

      // Set up config array
      $configArray = [
        'app_key' => $config->get('app_key'),
        'app_secret' => $config->get('app_secret'),
        'redirect_uri' => Url::fromRoute('instagram_basic_display.oauth', [], ['absolute' => TRUE])->toString()
      ];

      // Set confic of API class
      $this->instagramBasicDisplayApi->setConfig($configArray);

      // Get URL
      $url = $this->instagramBasicDisplayApi->getLoginUrl();
      $form['login_button'] = [
        '#markup' => '<a href="'.$url.'" class="button" target="_blank">Login with Instagram</a>',
      ];
      $form['images_button'] = [
        '#markup' => '<a href="/instagram/get-images" class="button" target="_blank">Pull images from Instagram</a>',
      ];
    }

    $form['app_key'] = [
      '#type' => 'textfield',
      '#title' => t('App Key'),
      '#required' => TRUE,
      '#default_value' => $config->get('app_key'),
    ];

    $form['app_secret'] = [
      '#type' => 'textfield',
      '#title' => t('App Secret'),
      '#required' => TRUE,
      '#default_value' => $config->get('app_secret'),
    ];

    $form['count'] = [
      '#type' => 'number',
      '#title' => t('Number of images to pull (max 20)'),
      '#required' => TRUE,
      '#default_value' => $config->get('count'),
    ];

    return parent::buildForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Get form values
    $form_values = $form_state->getValues();

    // Check required fields
    if ($form_values['app_key'] == '') {
      $form_state->setErrorByName('app_key', $this->t('The App Key field is required'));
    }
    if ($form_values['app_secret'] == '') {
      $form_state->setErrorByName('app_secret', $this->t('The App Secret field is required'));
    }
    if ($form_values['count'] == '') {
      $form_state->setErrorByName('count', $this->t('The count field is required'));
    }

  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get form values
    $form_values = $form_state->getValues();

    // Set config from from values
    $this->config('instagram_basic_display.settings')
      ->set('app_key', $form_values['app_key'])
      ->set('app_secret', $form_values['app_secret'])
      ->set('count', $form_values['count'])
      ->save();

    parent::submitForm($form, $form_state);

  }

}