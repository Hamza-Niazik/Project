<?php

namespace Drupal\group\Entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for relationships.
 */
class GroupRelationshipRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    if ($create_page_route = $this->getCreatePageRoute($entity_type)) {
      $collection->add("entity.group_relationship.create_page", $create_page_route);
    }

    if ($create_form_route = $this->getCreateFormRoute($entity_type)) {
      $collection->add("entity.group_relationship.create_form", $create_form_route);
    }

    return $collection;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAddPageRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('add-page') && $entity_type->getKey('bundle')) {
      $route = new Route($entity_type->getLinkTemplate('add-page'));
      $route
        ->setDefault('_controller', '\Drupal\group\Entity\Controller\GroupRelationshipController::addPage')
        ->setDefault('_title', 'Add existing content')
        ->setRequirement('_group_relationship_create_any_access', 'TRUE')
        ->setOption('_group_operation_route', TRUE);

      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('add-form')) {
      $route = new Route($entity_type->getLinkTemplate('add-form'));
      $route
        ->setDefaults([
          '_controller' => '\Drupal\group\Entity\Controller\GroupRelationshipController::addForm',
          '_title_callback' => '\Drupal\group\Entity\Controller\GroupRelationshipController::addFormTitle',
        ])
        ->setRequirement('_group_relationship_create_access', 'TRUE')
        ->setOption('_group_operation_route', TRUE);

      return $route;
    }
  }

  /**
   * Gets the create-page route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getCreatePageRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('create-page') && $entity_type->getKey('bundle')) {
      $route = new Route($entity_type->getLinkTemplate('create-page'));
      $route
        ->setDefault('_controller', '\Drupal\group\Entity\Controller\GroupRelationshipController::addPage')
        ->setDefault('_title', 'Add new content')
        ->setDefault('create_mode', TRUE)
        ->setRequirement('_group_relationship_create_any_entity_access', 'TRUE')
        ->setOption('_group_operation_route', TRUE);

      return $route;
    }
  }

  /**
   * Gets the create-form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getCreateFormRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('create-form')) {
      $route = new Route($entity_type->getLinkTemplate('create-form'));
      $route
        ->setDefaults([
          '_controller' => '\Drupal\group\Entity\Controller\GroupRelationshipController::createForm',
          '_title_callback' => '\Drupal\group\Entity\Controller\GroupRelationshipController::createFormTitle',
        ])
        ->setRequirement('_group_relationship_create_entity_access', 'TRUE')
        ->setOption('_group_operation_route', TRUE);

      return $route;
    }
  }

  /**
   * Gets the collection route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('collection') && $entity_type->hasListBuilderClass()) {
      $route = new Route($entity_type->getLinkTemplate('collection'));
      $route
        ->addDefaults([
          '_entity_list' => 'group_relationship',
          '_title_callback' => '\Drupal\group\Entity\Controller\GroupRelationshipController::collectionTitle',
        ])
        ->setRequirement('_group_permission', "access content overview")
        ->setOption('_group_operation_route', TRUE)
        ->setOption('parameters', [
          'group' => ['type' => 'entity:group'],
        ]);

      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getCanonicalRoute(EntityTypeInterface $entity_type) {
    return parent::getCanonicalRoute($entity_type)
      ->setRequirement('_group_owns_content', 'TRUE')
      ->setOption('parameters', [
        'group' => ['type' => 'entity:group'],
        'group_relationship' => ['type' => 'entity:group_relationship'],
      ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditFormRoute(EntityTypeInterface $entity_type) {
    return parent::getEditFormRoute($entity_type)
      ->setDefault('_title_callback', '\Drupal\group\Entity\Controller\GroupRelationshipController::editFormTitle')
      ->setRequirement('_group_owns_content', 'TRUE')
      ->setOption('_group_operation_route', TRUE)
      ->setOption('parameters', [
        'group' => ['type' => 'entity:group'],
        'group_relationship' => ['type' => 'entity:group_relationship'],
      ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeleteFormRoute(EntityTypeInterface $entity_type) {
    return parent::getDeleteFormRoute($entity_type)
      ->setRequirement('_group_owns_content', 'TRUE')
      ->setOption('_group_operation_route', TRUE)
      ->setOption('parameters', [
        'group' => ['type' => 'entity:group'],
        'group_relationship' => ['type' => 'entity:group_relationship'],
      ]);
  }

}
