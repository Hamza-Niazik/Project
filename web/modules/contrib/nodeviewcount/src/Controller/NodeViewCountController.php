<?php

namespace Drupal\nodeviewcount\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\nodeviewcount\NodeViewCountRecordsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for updating nodeviewcount statistics for node.
 */
class NodeViewCountController implements ContainerInjectionInterface {

  /**
   * Node view count records manager.
   *
   * @var \Drupal\nodeviewcount\NodeViewCountRecordsManager
   */
  protected $recordsManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a NodeViewCountController object.
   *
   * @param \Drupal\nodeviewcount\NodeViewCountRecordsManager $records_manager
   *   Node view count records manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(NodeViewCountRecordsManager $records_manager, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    $this->recordsManager = $records_manager;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('nodeviewcount.records_manager'),
      $container->get('module_handler'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Ajax request handler for updating node's view count statistics.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Symfony Request object.
   *   Request should have next query params:
   *     - nid : Id of node to update statistics for.
   *     - uid : Id of user which viewed the node.
   *     - view_mode : View mode if the node.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Update operation status response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function updateCounter(Request $request) {
    $data = ['status' => FALSE];
    $nid = $request->request->filter('nid', FALSE, FILTER_VALIDATE_INT);
    $uid = $request->request->filter('uid', FALSE, FILTER_VALIDATE_INT);
    $uip = $request->request->filter('uip', FALSE, FILTER_VALIDATE_IP);
    $view_mode = $request->get('view_mode');
    if ($nid !== FALSE && $uid !== FALSE) {
      /** @var \Drupal\node\NodeTypeInterface[] $node_types */
      $node_storage = $this->entityTypeManager->getStorage('node');
      $node = $node_storage->load($nid);
      $result = $this->moduleHandler->invokeAll('nodeviewcount_insert', [$node, $view_mode]);
      if (!in_array(FALSE, $result, TRUE)) {
        $this->recordsManager->insertRecord($uid, $nid, $uip);
        $data['status'] = TRUE;
      }
    }

    return new JsonResponse($data);
  }

}
