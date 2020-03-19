<?php

namespace Drupal\instagram_basic_display\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\instagram_basic_display\InstagramStorage;

/**
 * Provides a 'InstagramFeedBlock' block.
 *
 * @Block(
 *  id = "instagram_feed_block",
 *  admin_label = @Translation("Instagram Feed"),
 * )
 */
class InstagramFeedBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\instagram_basic_display\InstagramStorage definition.
   *
   * @var \Drupal\instagram_basic_display\InstagramStorage
   */
  protected $storage;

  /**
   * InstagramFeedBlock constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\instagram_basic_display\InstagramStorage $storage
   */
  public function __construct( array $configuration, $plugin_id, $plugin_definition, InstagramStorage $storage){
      parent::__construct($configuration, $plugin_id, $plugin_definition);
      $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
      return new static(
          $configuration,
          $plugin_id,
          $plugin_definition,
          $container->get('instagram_basic_display.instagram_storage')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

      $data = $this->storage->getImages();

      $build = [
          '#theme' => 'instagram',
          '#data' => $data,
      ];

      return $build;

  }


  /**
   * @return int
   */
  public function getCacheMaxAge(){

    return 14400;

  }


  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();
    
    $tags[] = 'instagram_media';

    return $tags;
  }


}
