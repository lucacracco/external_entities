<?php

namespace Drupal\external_entities\Plugin\ExternalEntityStorageConnection;

use Drupal\external_entities\Plugin\HttpClientBase;


/**
 * Wiki implementation of an external entity storage client.
 *
 * @ExternalEntityStorageConnection(
 *   id = "wiki_client",
 *   provider = "external_entities",
 *   name = "Wiki",
 *   description = "Wiki implementation of an external entity storage client",
 *   settings = {
 *    "default_limit" = 10,
 *    "page_parameter" = "page",
 *    "page_size_parameter" = "pagesize",
 *    "page_parameter_type" = "pagenum",
 *    "page_size_parameter_type" = "pagesize",
 *    "header_name" = NULL,
 *    "key" = NULL,
 *    "list" = {},
 *    "single" = {},
 *   },
 * )
 */
class WikiClient extends HttpClientBase {

  /**
   * {@inheritdoc}
   */
  public function delete(\Drupal\external_entities\Entity\ExternalEntityInterface $entity) {
    $this->httpClient->delete(
      $this->configuration['endpoint'] . '/' . $entity->externalId(),
      ['headers' => $this->getHttpHeaders()]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    /** @var \Drupal\external_entities\Entity\ExternalEntityTypeInterface $bundle */
    $bundle = $this->getExternalEntityType();

    $options = [
      'headers' => $this->getHttpHeaders(),
      'query' => [
        'pageids' => $id,
      ],
    ];
    if ($this->configuration['single']) {
      $options['query'] += $this->configuration['single'];
    }
    $response = $this->httpClient->get(
      $this->configuration['endpoint'],
      $options
    );
    $result = $this->decoder->getDecoder($bundle->getFormat())
      ->decode($response->getBody());
    return (object) $result['query']['pages'][$id];
  }

  /**
   * {@inheritdoc}
   */
  public function save(\Drupal\external_entities\Entity\ExternalEntityInterface $entity) {
    /** @var \Drupal\external_entities\Entity\ExternalEntityTypeInterface $bundle */
    $bundle = $this->getExternalEntityType();

    if ($entity->externalId()) {
      $response = $this->httpClient->put(
        $this->configuration['endpoint'] . '/' . $entity->externalId(),
        [
          'body' => (array) $entity->getMappedObject(),
          'headers' => $this->getHttpHeaders()
        ]
      );
      $result = SAVED_UPDATED;
    }
    else {
      $response = $this->httpClient->post(
        $this->configuration['endpoint'],
        [
          'body' => (array) $entity->getMappedObject(),
          'headers' => $this->getHttpHeaders()
        ]
      );
      $result = SAVED_NEW;
    }

    // @todo: is it standard REST to return the new entity?
    $object = (object) $this->decoder->getDecoder($bundle->getFormat())
      ->decode($response->getBody());
    $entity->mapObject($object);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function query(array $parameters) {

    /** @var \Drupal\external_entities\Entity\ExternalEntityTypeInterface $bundle */
    $bundle = $this->getExternalEntityType();

    $parameters = $this->configuration['list'];
    $parameters += $parameters;

    $response = $this->httpClient->get(
      $this->configuration['endpoint'],
      [
        'query' => $parameters,
        'headers' => $this->getHttpHeaders()
      ]
    );
    $results = $this->decoder->getDecoder($bundle->getFormat())
      ->decode($response->getBody());
    $results = $results['query']['categorymembers'];
    foreach ($results as &$result) {
      $result = ((object) $result);
    }
    return $results;
  }

}
