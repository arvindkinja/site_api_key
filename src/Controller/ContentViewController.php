<?php

namespace Drupal\site_api_key\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Defines a controller that will return a single node json response.
 */
class ContentViewController implements ContainerInjectionInterface {

  /**
   * The configuration service
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;

  /**
   * The Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates an ContentViewController object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct( ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager ) {
    $this->config = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create( ContainerInterface $container ) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }


  /**
   * Return json response for a given node ID
   * @param $api_key The key of the site
   * @param $nid The ID of the node
   */
  public function view( $api_key, $nid ) {
    // Get the site api key from the db
    $site_api_key = $this->config->get('siteapikey.settings');
    $key = $site_api_key->get('siteapikey');

    // Check the api_key is valid or not
    if ( $api_key != '' && $api_key != $key ) {
      throw new AccessDeniedHttpException();
    }

    // Load node object by node ID from db 
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node = $node_storage->load( $nid );

    // Check node is exists in the db or not
    if ($node == null) {
      throw new AccessDeniedHttpException();
    }

    // Check the node is published or not
    if ( ! $node->isPublished() ) {
      throw new AccessDeniedHttpException();
    }

    // Convert object in array
    $response = $node->toArray();

    // Return node result in json format
    return new JsonResponse($response);

  }
}
