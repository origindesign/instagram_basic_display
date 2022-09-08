<?php

/**
 * @file
 * Contains \Drupal\instagram_basic_display\Controller\InstagramBasicDisplayController.
 */

namespace Drupal\instagram_basic_display\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\instagram_basic_display\InstagramBasicDisplayApi;
use Drupal\instagram_basic_display\InstagramStorage;
use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Url;


class InstagramBasicDisplayController extends ControllerBase {


  protected $configFactory;
  protected $instagramBasicDisplayApi;
  protected $storage;
  protected $accessToken;
  protected $moduleConfig;
  protected $cacheTagsInvalidator;


  /**
   * InstagramBasicDisplayController constructor.
   *
   * @param \Drupal\instagram_basic_display\InstagramBasicDisplayApi $instagramBasicDisplayApi
   * @param \Drupal\instagram_basic_display\InstagramStorage $instagramStorage
   * @param \Drupal\Core\Cache\CacheTagsInvalidator $cacheTagsInvalidator
   */
  public function __construct(InstagramBasicDisplayApi $instagramBasicDisplayApi, InstagramStorage $instagramStorage, CacheTagsInvalidator $cacheTagsInvalidator) {
    $this->instagramBasicDisplayApi = $instagramBasicDisplayApi;
    $this->storage = $instagramStorage;
    $this->cacheTagsInvalidator = $cacheTagsInvalidator;
  }


  /**
   * @param ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('instagram_basic_display.instagram_basic_display_api'),
      $container->get('instagram_basic_display.instagram_storage'),
      $container->get('cache_tags.invalidator')
    );
  }


  /**
   * @param bool $truncate
   *
   * @return array
   */
  public function storeUserImages($truncate = false){

    // Get config
    $this->getConfig();

    // Refresh and set Access token
    $this->refreshAccessToken();
    $this->setAccessToken();

    // Set config for api calls
    $this->instagramBasicDisplayApi->setAccessToken($this->accessToken['access_token']);

    // Get images using api
    $images = $this->instagramBasicDisplayApi->getUserMedia('me', $this->moduleConfig['count']);

    // Save images to DB
    if($this->storage->storeImages($images->data,$truncate)){

      $this->cacheTagsInvalidator->invalidateTags(['instagram_media']);
      $response = $this->moduleConfig['count']." Instagram images have been saved to the database";

    }else{

      $response = "An error occured while saving instagram images to the database";
      drupal_set_message($response, 'error');

    }

    $build[] = array(
      '#type' => 'markup',
      '#markup' => $response,
    );
    $build['#cache']['max-age'] = 0;

    return $build;

  }


  /** Get authcode from callback
   *  Get shortlived token
   *  Get long lived token and save to database
   * @param bool $truncate
   * @return array|bool
   */
  public function oAuth(){

    // Get config
    $this->getConfig();

    // Set config for api calls
    $this->instagramBasicDisplayApi->setConfig($this->moduleConfig);

    // Get the OAuth callback code
    //$code = '';
    $code = \Drupal::request()->query->get('code');

    // Get the short lived access token (valid for 1 hour)
    $token = $this->instagramBasicDisplayApi->getOAuthToken($code, true);
    ksm('oauth:');
    ksm($token);
    // Exchange this token for a long lived token (valid for 60 days)
    $token = $this->instagramBasicDisplayApi->getLongLivedToken($token);
    ksm('long:');
    ksm($token);

    // Save token into DB
    if($this->storage->setAccessToken($token)){
      $response = '<h2>Access token successfully saved to database</h2>';
    }else{
      $response = '<h2>Error saving access token to database</h2>';
    }

    $build[] = array(
      '#type' => 'markup',
      '#markup' => $response,
    );
    $build['#cache']['max-age'] = 0;

    return $build;

  }


  /** Get current token, refresh, and save in db
   * @return bool
   */
  private function refreshAccessToken(){

    // Get current access token from DB
    $current_token = $this->storage->getAccessToken();

    // Get refresh token
    $refresh_token = $this->instagramBasicDisplayApi->refreshToken($current_token['access_token']);

    // Save refresh token to db
    if($this->storage->setAccessToken($refresh_token)){
      return true;
    }else{
      return false;
    }

  }


  /** Set access token in class
   * @param $token
   */
  private function setAccessToken(){

    // Get access token from DB
    $token = $this->storage->getAccessToken();

    // Set token in class
    $this->accessToken = $token;

  }


  /** Set moduleConfig in class
   * @param $token
   */
  private function getConfig(){

    if(!$this->moduleConfig){

      // Get module config
      $config = $this->config('instagram_basic_display.settings');

      $configArray = [
        'app_key' => $config->get('app_key'),
        'app_secret' => $config->get('app_secret'),
        'redirect_uri' => Url::fromRoute('instagram_basic_display.oauth', [], ['absolute' => TRUE])->toString(),
        'count' => $config->get('count')
      ];

      // Set token in class
      $this->moduleConfig = $configArray;

    }

  }


}
