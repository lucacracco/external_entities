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
    $options = [
      'headers' => $this->getHttpHeaders(),
      'query' => [
        'pageids' => $id,
      ],
    ];
    if ($this->configuration['parameters']['single']) {
      $options['query'] += $this->configuration['parameters']['single'];
    }
    $response = $this->httpClient->get(
      $this->configuration['endpoint'],
      $options
    );
    $result = $this->decoder->getDecoder($this->configuration['format'])
      ->decode($response->getBody());
    return (object) $result['query']['pages'][$id];
  }

  /**
   * {@inheritdoc}
   */
  public function save(\Drupal\external_entities\Entity\ExternalEntityInterface $entity) {
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
    $object = (object) $this->decoder->getDecoder($this->configuration['format'])
      ->decode($response->getBody());
    $entity->mapObject($object);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function query(array $parameters) {
    $response = $this->httpClient->get(
      $this->configuration['endpoint'],
      [
//        'query' => $parameters + $this->configuration['parameters']['list'],
        'query' => $parameters,
        'headers' => $this->getHttpHeaders()
      ]
    );
    $results = $this->decoder->getDecoder($this->configuration['format'])
      ->decode($response->getBody());
    $results = $results['query']['categorymembers'];
    foreach ($results as &$result) {
      $result = ((object) $result);
    }
    return $results;
  }

}
