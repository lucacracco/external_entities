<?php

/**
 * @file
 * Contains \Drupal\external_entities\Entity\Query\External\QueryFactory.
 */

namespace Drupal\external_entities\Entity\Query\External;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryException;
use Drupal\Core\Entity\Query\QueryFactoryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\external_entities\Decoder\ResponseDecoderFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Factory class creating entity query objects for the external backend.
 *
 * @see \Drupal\external_entities\Entity\Query\External\Query
 */
class QueryFactory implements QueryFactoryInterface {

  /**
   * The namespace of this class, the parent class etc.
   *
   * @var array
   */
  protected $namespaces;

  /**
   * The external storage connection manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $storageConnectionManager;

  /**
   * The decoder.
   *
   * @var \Drupal\external_entities\Decoder\ResponseDecoderFactoryInterface
   */
  protected $decoder;

  /**
   * Stores the entity manager used by the query.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a QueryFactory object.
   *
   * {@inheritdoc}
   */
  public function __construct(PluginManagerInterface $storage_connection_manager, ResponseDecoderFactoryInterface $decoder, EntityManagerInterface $entity_manager) {
    $this->namespaces = QueryBase::getNamespaces($this);
    $this->storageConnectionManager = $storage_connection_manager;
    $this->decoder = $decoder;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function get(EntityTypeInterface $entity_type, $conjunction) {
    if ($conjunction == 'OR') {
      throw new QueryException("External entity queries do not support OR conditions.");
    }
    $class = QueryBase::getClass($this->namespaces, 'Query');
    return new $class($entity_type, $conjunction, $this->namespaces, $this->storageConnectionManager, $this->decoder, $this->entityManager);
  }

  /**
   * {@inheritdoc}
   */
  public function getAggregate(EntityTypeInterface $entity_type, $conjunction) {
    throw new QueryException("External entity queries do not support aggregate queries.");
  }

}