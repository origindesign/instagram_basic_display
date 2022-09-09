<?php

namespace Drupal\instagram_basic_display;

use Drupal\Core\Database\Connection;


class InstagramStorage {



  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;


  /**
   * InstagramStorage constructor.
   * @param Connection $database
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }


  /**
   * @param $images
   * @param bool $truncate
   *
   * @return bool
   * @throws \Exception
   */
  public function storeImages($images,$truncate = false){

    if($truncate){
      $query = $this->database->truncate('instagram_media');
      $query->execute();
    }

    foreach($images as $key => $image){

      $query = $this->database->merge('instagram_media');
      $query->key(array(
        'iid' => $key+1
      ));
      $query->fields(array(
        'id' => $image->id,
        'caption' => $image->caption,
        'media_type' => $image->media_type,
        'timestamp' => $image->timestamp,
        'username' => $image->username,
        'media_url' => (isset($image->thumbnail_url) ? $image->thumbnail_url : $image->media_url),
        'permalink' => $image->permalink,
      ));

      if ( !$query->execute() ){
        return false;
      }

    }

    return true;

  }


  /**
   * Get images from DB
   * @return array
   */
  public function getImages(){

    $query = $this->database->select('instagram_media','instagram');
    $query->fields('instagram');
    $result = $query->execute()->fetchAll();

    $data = array();

    foreach($result as $record) {
      $data[] = array(
        'id' => $record->id,
        'caption' => $record->caption,
        'media_type' => $record->media_type,
        'timestamp' => $record->timestamp,
        'username' => $record->username,
        'media_url' => $record->media_url,
        'permalink' => $record->permalink,
      );
    }

    return $data;

  }


  /** Set access token in DB
   * @return bool
   */
  public function setAccessToken($token){
    $query = $this->database->merge('instagram_access_token');
    $query->key(array(
      'iid' => 1
    ));
    $query->fields(array(
      'access_token' => $token->access_token,
      'expires_in' => $token->expires_in,
      'token_type' => $token->token_type
    ));

    if ( !$query->execute() ){
      return false;
    }

    return true;

  }



  /**
   * Get access_token from DB
   * @return array
   */
  public function getAccessToken(){

    $query = $this->database->select('instagram_access_token','instagram_access_token');
    $query->fields('instagram_access_token');
    $result = $query->execute()->fetchAll();

    $data = array();

    foreach($result as $record) {
      $data = array(
        'access_token' => $record->access_token,
        'expires_in' => $record->expires_in,
        'token_type' => $record->token_type
      );
    }

    return $data;

  }


}
