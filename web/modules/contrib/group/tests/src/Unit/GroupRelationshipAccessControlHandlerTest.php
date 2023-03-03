<?php

namespace Drupal\Tests\group\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Access\GroupRelationshipAccessControlHandler;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Entity\GroupRelationshipTypeInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the relationship access control handler.
 *
 * @coversDefaultClass \Drupal\group\Entity\Access\GroupRelationshipAccessControlHandler
 * @group group
 */
class GroupRelationshipAccessControlHandlerTest extends UnitTestCase {

  /**
   * The account to test with.
   *
   * @var \Drupal\Core\Session\AccountInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $account;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeManager;

  /**
   * The group relation type manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $groupRelationTypeManager;

  /**
   * The access control handler.
   *
   * @var \Drupal\group\Entity\Access\GroupRelationshipAccessControlHandler|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $accessControlHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->account = $this->prophesize(AccountInterface::class);
    $this->account->id()->willReturn(1986);

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->groupRelationTypeManager = $this->prophesize(GroupRelationTypeManagerInterface::class);
    $moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $moduleHandler->invokeAll(Argument::cetera())->willReturn([]);

    $container = $this->prophesize(ContainerInterface::class);
    $container->get('entity_type.manager')->willReturn($this->entityTypeManager->reveal());
    $container->get('group_relation_type.manager')->willReturn($this->groupRelationTypeManager->reveal());
    $container->get('module_handler')->willReturn($moduleHandler->reveal());
    \Drupal::setContainer($container->reveal());

    $entityType = $this->prophesize(EntityTypeInterface::class);
    $this->accessControlHandler = GroupRelationshipAccessControlHandler::createInstance(
      $container->reveal(),
      $entityType->reveal()
    );
  }

  /**
   * Tests access.
   *
   * @covers ::checkAccess
   * @uses ::access
   */
  public function testCheckAccess() {
    $language = $this->prophesize(LanguageInterface::class);
    $language->getId()->willReturn('nl');

    $group_relationship = $this->prophesize(GroupRelationshipInterface::class);
    $group_relationship->id()->willReturn(1337);
    $group_relationship->uuid()->willReturn('baz');
    $group_relationship->language()->willReturn($language->reveal());
    $group_relationship->getRevisionId()->willReturn(9001);
    $group_relationship->getEntityTypeId()->willReturn('group_relationship');
    $group_relationship->getPluginId()->willReturn('bar');

    $access_result = AccessResult::allowed();
    $access_control = $this->prophesize(AccessControlInterface::class);
    $access_control->relationshipAccess($group_relationship->reveal(), 'some_operation', $this->account->reveal(), TRUE)->shouldBeCalled()->willReturn($access_result);
    $this->groupRelationTypeManager->getAccessControlHandler('bar')->willReturn($access_control->reveal());

    $result = $this->accessControlHandler->access(
      $group_relationship->reveal(),
      'some_operation',
      $this->account->reveal()
    );
    $this->assertEquals($access_result->isAllowed(), $result);
  }

  /**
   * Tests create access.
   *
   * @covers ::checkCreateAccess
   * @uses ::createAccess
   */
  public function testCheckCreateAccess() {
    $group = $this->prophesize(GroupInterface::class);

    $relationship_type = $this->prophesize(GroupRelationshipTypeInterface::class);
    $relationship_type->getPluginId()->willReturn('bar');
    $relationship_type_storage = $this->prophesize(GroupRelationshipTypeStorageInterface::class);
    $relationship_type_storage->load('foo')->willReturn($relationship_type->reveal());
    $this->entityTypeManager->getStorage('group_relationship_type')->willReturn($relationship_type_storage->reveal());

    $access_result = AccessResult::allowed();
    $access_control = $this->prophesize(AccessControlInterface::class);
    $access_control->relationshipCreateAccess($group->reveal(), $this->account->reveal(), TRUE)->shouldBeCalled()->willReturn($access_result);
    $this->groupRelationTypeManager->getAccessControlHandler('bar')->willReturn($access_control->reveal());

    $result = $this->accessControlHandler->createAccess(
      'foo',
      $this->account->reveal(),
      ['group' => $group->reveal()]
    );
    $this->assertEquals($access_result->isAllowed(), $result);
  }

}
