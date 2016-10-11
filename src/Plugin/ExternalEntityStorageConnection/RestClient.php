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

    // Call.
    $this->httpClient->delete(
      $this->configuration['endpoint'] . '/' . $entity->externalId(),
      ['headers' => $this->getHttpHeaders()]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {

    // Retrieve and build options call.
    $options = [
      'headers' => $this->getHttpHeaders(),
      'query' => [],
    ];
    if ($this->configuration['single']) {
      $options['query'] += $this->configuration['single'];
    }

    // Call.
    $response = $this->httpClient->get(
      $this->configuration['endpoint'] . '/' . $id,
      $options
    );

    // TODO: control reponse has code 200. otherwise create exception.
    $body = $response->getBody();

    // Decode.
    $result = $this->decoder->getDecoder($this->externalEntityType->getFormat())
      ->decode($body);

    // Retrieve result object.
    $result = (object) $result[0];
    return $result;
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
    $object = (object) $this->decoder->getDecoder($this->externalEntityType->getFormat())
      ->decode($response->getBody());
    $entity->mapFromStorageRecords($object);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function query(array $parameters) {

    // Retrieve and build parameters for call.
    $parameters = $this->configuration['list'];
    $parameters += $parameters;

    // Call.
    $response = $this->httpClient->get(
      $this->configuration['endpoint'],
      [
        'query' => $parameters,
        'headers' => $this->getHttpHeaders()
      ]
    );

    // TODO: check response code.
    $body = $response->getBody();

    $results = $this->decoder->getDecoder($this->externalEntityType->getFormat())
      ->decode($body);

    // Retrieve result objects.
    foreach ($results as &$result) {
      $result = ((object) $result);
    }
    return $results;
  }

}
