<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\Tests\group\Traits\NodeTypeCreationTrait;
use Drupal\user\RoleInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the relationship create access for groups.
 *
 * @group group
 */
class GroupRelationshipCreateAccessTest extends GroupKernelTestBase {

  use NodeTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group_test_plugin', 'node'];

  /**
   * The access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The group type to run this test on.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupType;

  /**
   * The group admin role.
   *
   * @var \Drupal\group\Entity\GroupRoleInterface
   */
  protected $adminRole;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('entity_test_with_owner');
    $this->createNodeType(['type' => 'page']);

    $this->accessManager = $this->container->get('access_manager');
    $this->routeProvider = $this->container->get('router.route_provider');
    $this->groupType = $this->createGroupType([
      'id' => 'create_access_test',
      'creator_membership' => FALSE,
    ]);

    // Enable the test plugins on the group type.
    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);
    $storage->save($storage->createFromPlugin($this->groupType, 'entity_test_relation'));
    $storage->save($storage->createFromPlugin($this->groupType, 'node_relation:page'));

    $this->adminRole = $this->createGroupRole([
      'group_type' => $this->groupType->id(),
      'scope' => PermissionScopeInterface::INDIVIDUAL_ID,
      'admin' => TRUE,
    ]);
  }

  /**
   * Tests access to the create/add overview page.
   *
   * @dataProvider pageAccessProvider
   */
  public function testPageAccess($route, $outsider_permissions, $member_permissions, $outsider_access, $member_access, $admin_access, $message) {
    $outsider = $this->createUser();
    $member = $this->createUser();
    $admin = $this->createUser();

    $this->createGroupRole([
      'group_type' => $this->groupType->id(),
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => $outsider_permissions,
    ]);
    $this->createGroupRole([
      'group_type' => $this->groupType->id(),
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => $member_permissions,
    ]);

    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $group->addMember($member);
    $group->addMember($admin, ['group_roles' => [$this->adminRole->id()]]);

    $request = $this->createRequest($route, $group);
    $this->assertSame($outsider_access, $this->accessManager->checkRequest($request, $outsider), $message);
    $this->assertSame($member_access, $this->accessManager->checkRequest($request, $member), $message);
    $this->assertSame($admin_access, $this->accessManager->checkRequest($request, $admin), $message);
  }

  /**
   * Data provider for testPageAccess().
   *
   * @return array
   *   A list of testPageAccess method arguments.
   */
  public function pageAccessProvider() {
    $cases = [];

    $cases['create-page-access-one'] = [
      'entity.group_relationship.create_page',
      [],
      ['create entity_test_relation entity'],
      FALSE,
      TRUE,
      TRUE,
      'Testing the _group_relationship_create_any_entity_access route access check with create access from one plugin',
    ];

    $cases['create-page-access-multi'] = [
      'entity.group_relationship.create_page',
      [],
      ['create entity_test_relation entity', 'create node_relation:page entity'],
      FALSE,
      TRUE,
      TRUE,
      'Testing the _group_relationship_create_any_entity_access route access check with create access from multiple plugins',
    ];

    $cases['create-page-with-add-access'] = [
      'entity.group_relationship.create_page',
      [],
      ['create entity_test_relation relationship'],
      FALSE,
      FALSE,
      TRUE,
      'Testing the _group_relationship_create_any_entity_access route access check with add access from one plugin',
    ];

    $cases['add-page-access-one'] = [
      'entity.group_relationship.add_page',
      [],
      ['create entity_test_relation relationship'],
      FALSE,
      TRUE,
      TRUE,
      'Testing the _group_relationship_create_any_access route access check with add access from one plugin',
    ];

    $cases['add-page-access-multi'] = [
      'entity.group_relationship.add_page',
      [],
      ['create entity_test_relation relationship', 'create node_relation:page relationship'],
      FALSE,
      TRUE,
      TRUE,
      'Testing the _group_relationship_create_any_access route access check with add access from multiple plugins',
    ];

    $cases['add-page-with-create-access'] = [
      'entity.group_relationship.add_page',
      [],
      ['create entity_test_relation entity'],
      FALSE,
      FALSE,
      TRUE,
      'Testing the _group_relationship_create_any_access route access check with create access from one plugin',
    ];

    return $cases;
  }

  /**
   * Tests access to the create/add form.
   *
   * @dataProvider formAccessProvider
   */
  public function testFormAccess($route, $outsider_permissions, $member_permissions, $outsider_access, $member_access, $admin_access, $message) {
    $outsider = $this->createUser();
    $member = $this->createUser();
    $admin = $this->createUser();

    $this->createGroupRole([
      'group_type' => $this->groupType->id(),
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => $outsider_permissions,
    ]);
    $this->createGroupRole([
      'group_type' => $this->groupType->id(),
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => $member_permissions,
    ]);

    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $group->addMember($member);
    $group->addMember($admin, ['group_roles' => [$this->adminRole->id()]]);

    $request = $this->createRequest($route, $group, 'entity_test_relation');
    $this->assertSame($outsider_access, $this->accessManager->checkRequest($request, $outsider), $message);
    $this->assertSame($member_access, $this->accessManager->checkRequest($request, $member), $message);
    $this->assertSame($admin_access, $this->accessManager->checkRequest($request, $admin), $message);
  }

  /**
   * Data provider for testFormAccess().
   *
   * @return array
   *   A list of testFormAccess method arguments.
   */
  public function formAccessProvider() {
    $cases = [];

    $cases['create-form-access'] = [
      'entity.group_relationship.create_form',
      [],
      ['create entity_test_relation entity'],
      FALSE,
      TRUE,
      TRUE,
      'Testing the _group_relationship_create_entity_access route access check with create access',
    ];

    $cases['create-form-access-wrong-plugin'] = [
      'entity.group_relationship.create_form',
      [],
      ['create node_relation:page entity'],
      FALSE,
      FALSE,
      TRUE,
      'Testing the _group_relationship_create_entity_access route access check with create access from the wrong plugin',
    ];

    $cases['create-form-with-add-access'] = [
      'entity.group_relationship.create_form',
      [],
      ['create entity_test_relation relationship'],
      FALSE,
      FALSE,
      TRUE,
      'Testing the _group_relationship_create_entity_access route access check with add access',
    ];

    $cases['add-form-access'] = [
      'entity.group_relationship.add_form',
      [],
      ['create entity_test_relation relationship'],
      FALSE,
      TRUE,
      TRUE,
      'Testing the _group_relationship_create_access route access check with add access',
    ];

    $cases['add-form-access-wrong-plugin'] = [
      'entity.group_relationship.add_form',
      [],
      ['create node_relation:page relationship'],
      FALSE,
      FALSE,
      TRUE,
      'Testing the _group_relationship_create_access route access check with add access from the wrong plugin',
    ];

    $cases['add-form-with-create-access'] = [
      'entity.group_relationship.add_form',
      [],
      ['create entity_test_relation entity'],
      FALSE,
      FALSE,
      TRUE,
      'Testing the _group_relationship_create_access route access check with create access',
    ];

    return $cases;
  }

  /**
   * Creates a request for a specific route.
   *
   * @param string $route_name
   *   The route name.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group.
   * @param string|null $plugin_id
   *   (optional) The plugin ID.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The request.
   */
  protected function createRequest($route_name, GroupInterface $group, $plugin_id = NULL) {
    $params = ['group' => $group->id()];
    $attributes = ['group' => $group];

    if ($plugin_id) {
      $params['plugin_id'] = $plugin_id;
      $attributes['plugin_id'] = $plugin_id;
    }

    $attributes[RouteObjectInterface::ROUTE_NAME] = $route_name;
    $attributes[RouteObjectInterface::ROUTE_OBJECT] = $this->routeProvider->getRouteByName($route_name);
    $attributes['_raw_variables'] = new ParameterBag($params);

    $request = Request::create(Url::fromRoute($route_name, $params)->toString());
    $request->attributes->add($attributes);

    // Push the request to the request stack so `current_route_match` works.
    $this->container->get('request_stack')->push($request);
    return $request;
  }

}
