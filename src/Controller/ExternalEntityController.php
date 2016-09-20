<?php

/**
 * @file
 * Contains \Drupal\external_entities\Controller\ExternalEntityController.
 */

namespace Drupal\external_entities\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\external_entities\Entity\ExternalEntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Node routes.
 */
class ExternalEntityController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a ExternalEntityController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }


  /**
   * Displays add external entity links for available types.
   *
   * Redirects to external-entity/add/[type] if only one type is available.
   *
   * @return array
   *   A render array for a list of the external entity types that can be added;
   *   however, if there is only one type defined for the site, the function
   *   redirects to the add page for that one type and does not return at all.
   *
   * @see node_menu()
   */
  public function addPage() {
    $content = [];

    // Only use node types the user has access to.
    foreach ($this->entityManager()
               ->getStorage('external_entity_type')
               ->loadMultiple() as $type) {
      if ($this->entityManager()
        ->getAccessControlHandler('external_entity')
        ->createAccess($type->id())
      ) {
        $content[$type->id()] = $type;
      }
    }

    // Bypass the node/add listing if only one content type is available.
    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect('external_entity.add', ['external_entity_type' => $type->id()]);
    }

    return [
      '#theme' => 'external_entities_add_list',
      '#content' => $content,
    ];
  }

  /**
   * Provides the node submission form.
   *
   * @param \Drupal\external_entities\Entity\ExternalEntityTypeInterface $external_entity_type
   *   The external type entity for the external entity.
   *
   * @return array
   *   An external entity submission form.
   */
  public function add(ExternalEntityTypeInterface $external_entity_type) {
    $entity = $this->entityManager()->getStorage('external_entity')->create([
      'type' => $external_entity_type->id(),
    ]);

    $form = $this->entityFormBuilder()->getForm($entity);

    return $form;
  }

  /**
   * The _title_callback for the external_entity.add route.
   *
   * @param \Drupal\external_entities\Entity\ExternalEntityTypeInterface $external_entity_type
   *   The current external entity.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(ExternalEntityTypeInterface $external_entity_type) {
    return $this->t('Create @name', ['@name' => $external_entity_type->label()]);
  }

}
