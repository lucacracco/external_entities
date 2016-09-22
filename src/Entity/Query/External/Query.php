<?php

namespace Drupal\external_entities\Entity\Query\External;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\ConditionInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryException;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\external_entities\Decoder\ResponseDecoderFactoryInterface;

/**
 * The SQL storage entity query class.
 */
class Query extends QueryBase implements QueryInterface {

  /**
   * The parameters to send to the external entity storage connection.
   *
   * @var array
   */
  protected $parameters = [];

  /**
   * An array of fields keyed by the field alias.
   *
   * Each entry correlates to the arguments of
   * \Drupal\Core\Database\Query\SelectInterface::addField(), so the first one
   * is the table alias, the second one the field and the last one optional the
   * field alias.
   *
   * @var array
   */
  protected $fields = [];

  /**
   * An array of strings added as to the group by, keyed by the string to avoid
   * duplicates.
   *
   * @var array
   */
  protected $groupBy = [];

  /**
   * Stores the entity manager used by the query.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The external storage connection manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $storageConnectorManager;

  /**
   * The decoder.
   *
   * @var \Drupal\external_entities\Decoder\ResponseDecoderFactoryInterface
   */
  protected $decoder;

  /**
   * Storage connection instance.
   *
   * @var \Drupal\external_entities\Plugin\ExternalEntityStorageConnectionInterface
   */
  protected $storageConnection;

  /**
   * Constructs a query object.
   *
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, $conjunction, array $namespaces, PluginManagerInterface $storage_connection_manager, ResponseDecoderFactoryInterface $decoder, EntityManagerInterface $entity_manager) {
    parent::__construct($entity_type, $conjunction, $namespaces);
    $this->storageConnectorManager = $storage_connection_manager;
    $this->decoder = $decoder;
    $this->entityManager = $entity_manager;
  }


  /**
   * Implements \Drupal\Core\Entity\Query\QueryInterface::execute().
   */
  public function execute() {
    return $this
      ->prepare()
      ->compile()
      ->addSort()
      ->finish()
      ->result();
  }

  /**
   * Prepares the basic query with proper metadata/tags and base fields.
   *
   * @throws \Drupal\Core\Entity\Query\QueryException
   *   Thrown if the base table does not exists.
   *
   * @return \Drupal\Core\Entity\Query\Sql\Query
   *   Returns the called object.
   */
  protected function prepare() {
    if ($this->amountOfBundleConditions() !== 1) {
      throw new QueryException("You must specify a single bundle for external entity queries.");
    }
    $this->checkConditions();
    return $this;
  }

  /**
   * Check if all conditions are valid.
   *
   * @param \Drupal\Core\Entity\Query\ConditionInterface $condition
   *   The conditions to check.
   *
   * @throws QueryException
   */
  protected function checkConditions(\Drupal\Core\Entity\Query\ConditionInterface $condition = NULL) {
    if (is_null($condition)) {
      $condition = $this->condition;
    }
    foreach ($condition->conditions() as $c) {
      if ($c['field'] instanceOf ConditionInterface) {
        $this->checkConditions($c['field']);
      }
      elseif ($c['operator'] && !in_array($c['operator'], $this->supportedOperators())) {
        throw new QueryException("Operator {$c['operator']} is not supported by external entity queries.");
      }
    }
  }

  /**
   * Returns the supported condition operators.
   *
   * @return array
   *   The supported condition operators.
   */
  protected function supportedOperators() {
    return [
      '=',
      'IN',
    ];
  }

  /**
   * Get the amount of bundle conditions.
   */
  protected function amountOfBundleConditions(\Drupal\Core\Entity\Query\ConditionInterface $condition = NULL) {
    $amount = 0;
    if (is_null($condition)) {
      $condition = $this->condition;
    }
    foreach ($condition->conditions() as $c) {
      if ($c['field'] instanceOf ConditionInterface) {
        $amount += $this->numberOfBundleConditions($c['field']);
      }
      else {
        if ($c['field'] == $this->entityType->getKey('bundle')) {
          $amount += is_array($c['value']) ? count($c['value']) : 1;
        }
      }
    }
    return $amount;
  }

  /**
   * Compiles the conditions.
   *
   * @return \Drupal\Core\Entity\Query\Sql\Query
   *   Returns the called object.
   */
  protected function compile() {
    $this->condition->compile($this);
    return $this;
  }

  /**
   * Adds the sort to the build query.
   *
   * @return \Drupal\Core\Entity\Query\Sql\Query
   *   Returns the called object.
   */
  protected function addSort() {
    // TODO.
    return $this;
  }

  /**
   * Finish the query by adding fields, GROUP BY and range.
   *
   * @return \Drupal\Core\Entity\Query\Sql\Query
   *   Returns the called object.
   */
  protected function finish() {
    return $this;
  }

  /**
   * Executes the query and returns the result.
   *
   * @return int|array
   *   Returns the query result as entity IDs.
   */
  protected function result() {
    if ($this->count) {
      return count($this->getStorageConnection()->query($this->parameters));
    }
    // Return a keyed array of results. The key is either the revision_id or
    // the entity_id depending on whether the entity type supports revisions.
    // The value is always the entity id.
    // TODO.
    $query_results = $this->getStorageConnection()->query($this->parameters);
    $result = [];
    $bundle_id = $this->getBundle();
    $bundle = $this->entityManager->getStorage($this->entityType->getBundleEntityType())
      ->load($bundle_id);
    foreach ($query_results as $query_result) {
      $id = $bundle_id . '-' . $query_result->{$bundle->getFieldMapping('id')};
      $result[$id] = $id;
    }
    return $result;
  }

  /**
   * Get the storage connection for a bundle.
   *
   * @return \Drupal\external_entities\Plugin\ExternalEntityStorageConnectionInterface
   *   The external entity storage connection.
   */
  protected function getStorageConnection() {
    if (!$this->storageConnection) {
      /** @var \Drupal\external_entities\Entity\ExternalEntityTypeInterface $bundle */
      $bundle = $this->entityManager->getStorage($this->entityType->getBundleEntityType())
        ->load($this->getBundle());
      $this->storageConnection = $bundle->getConnection();
    }
    return $this->storageConnection;
  }

  /**
   * Determines whether the query requires GROUP BY and ORDER BY MIN/MAX.
   *
   * @return bool
   */
  protected function isSimpleQuery() {
    return (!$this->pager && !$this->range && !$this->count);
  }

  /**
   * Implements the magic __clone method.
   *
   * Reset fields and GROUP BY when cloning.
   */
  public function __clone() {
    parent::__clone();
    $this->fields = [];
    $this->groupBy = [];
  }

  /**
   * Set a parameter.
   */
  public function setParameter($key, $value) {
    if ($key == $this->entityType->getKey('bundle')) {
      return FALSE;
    }
    $this->parameters[$key] = is_array($value) ? implode($value, ',') : $value;
  }

  /**
   * Get the bundle for this query.
   */
  protected function getBundle(\Drupal\Core\Entity\Query\ConditionInterface $condition = NULL) {
    if (is_null($condition)) {
      $condition = $this->condition;
    }
    foreach ($condition->conditions() as $c) {
      if ($c['field'] instanceOf ConditionInterface) {
        $bundle = $this->getBundle($c['field']);
        if ($bundle) {
          return $bundle;
        }
      }
      else {
        if ($c['field'] == $this->entityType->getKey('bundle')) {
          return is_array($c['value']) ? reset($c['value']) : $c['value'];
        }
      }
    }
    return FALSE;
  }
}
