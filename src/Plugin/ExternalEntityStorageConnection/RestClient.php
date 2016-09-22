<?php

namespace Drupal\external_entities\Plugin\ExternalEntityStorageConnection;

use Drupal\external_entities\Plugin\HttpClientBase;

/**
 * REST implementation of an external entity storage connection.
 *
 * @ExternalEntityStorageConnection(
 *   id = "rest_client",
 *   provider = "external_entities",
 *   name = "REST",
 *   description = "REST implementation of an external entity storage connection",
 *   settings = {
 *    "endpoint" = "",
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
class RestClient extends HttpClientBase {

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
    $response = $this->httpClient->get(
      $this->configuration['endpoint'] . '/' . $id,
      ['headers' => $this->getHttpHeaders()]
    );
    return (object) $this->decoder->getDecoder($this->configuration['format'])
      ->decode($response->getBody());
  }

  /**
   * {@inheritdoc}
   */
  public function save(\Drupal\external_entities\Entity\ExternalEntityInterface $entity) {
    if ($entity->externalId()) {
      $response = $this->httpClient->put(
        $this->configuration['endpoint'] . '/' . $entity->externalId(),
        [
          'form_params' => (array) $entity->getMappedObject(),
          'headers' => $this->getHttpHeaders()
        ]
      );
      $result = SAVED_UPDATED;
    }
    else {
      $response = $this->httpClient->post(
        $this->configuration['endpoint'],
        [
          'form_params' => (array) $entity->getMappedObject(),
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
        'query' => $parameters,
        'headers' => $this->getHttpHeaders()
      ]
    );
    $results = $this->decoder->getDecoder($this->configuration['format'])
      ->decode($response->getBody());
    foreach ($results as &$result) {
      $result = ((object) $result);
    }
    return $results;
  }

}
