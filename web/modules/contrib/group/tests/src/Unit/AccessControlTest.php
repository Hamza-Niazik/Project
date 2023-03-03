<?php

namespace Drupal\Tests\group\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\Storage\GroupRelationshipStorageInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationType;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface;
use Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface;
use Drupal\group\Plugin\Group\RelationHandler\AccessControlTrait;
use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface;
use Drupal\group\Plugin\Group\RelationHandlerDefault\AccessControl;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\EntityOwnerInterface;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the default group relation access_control handler.
 *
 * @coversDefaultClass \Drupal\group\Plugin\Group\RelationHandlerDefault\AccessControl
 * @group group
 */
class AccessControlTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $cache_context_manager = $this->prophesize(CacheContextsManager::class);
    $cache_context_manager->assertValidTokens(Argument::any())->willReturn(TRUE);

    $container = $this->prophesize(ContainerInterface::class);
    $container->get('cache_contexts_manager')->willReturn($cache_context_manager->reveal());
    \Drupal::setContainer($container->reveal());
  }

  /**
   * Tests whether the operation is supported.
   *
   * @param bool $expected
   *   The expected outcome.
   * @param string $plugin_id
   *   The plugin ID.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $definition
   *   The plugin definition.
   * @param string $operation
   *   The permission operation. Usually "create", "view", "update" or "delete".
   * @param string $target
   *   The target of the operation. Can be 'relationship' or 'entity'.
   * @param string|false $permission
   *   The operation permission.
   * @param string|false $own_permission
   *   The owner operation permission.
   * @param bool $is_ownable
   *   Whether the entity can be owned.
   * @param bool $is_publishable
   *   Whether the entity can be (un)published.
   *
   * @covers ::supportsOperation
   * @dataProvider supportsOperationProvider
   */
  public function testSupportsOperation($expected, $plugin_id, GroupRelationTypeInterface $definition, $operation, $target, $permission, $own_permission, $is_ownable, $is_publishable) {
    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->entityClassImplements(EntityPublishedInterface::class)->willReturn($is_publishable);
    $entity_type->entityClassImplements(EntityOwnerInterface::class)->willReturn($is_ownable);

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getDefinition($definition->getEntityTypeId())->willReturn($entity_type->reveal());

    $permission_provider = $this->prophesize(PermissionProviderInterface::class);
    $permission_provider->getPermission($operation, $target, 'any')->willReturn($permission);
    if ($target === 'relationship' || $is_ownable) {
      $permission_provider->getPermission($operation, $target, 'own')->willReturn($own_permission);
    }
    else {
      $permission_provider->getPermission($operation, $target, 'own')->shouldNotBeCalled();
    }
    $access_control_handler = $this->createAccessControlHandler($plugin_id, $definition, $permission_provider->reveal(), $entity_type_manager->reveal());

    $this->assertSame($expected, $access_control_handler->supportsOperation($operation, $target));
  }

  /**
   * Data provider for testSupportsOperation().
   *
   * @return array
   *   A list of testSupportsOperation method arguments.
   */
  public function supportsOperationProvider() {
    foreach (['relationship', 'entity'] as $target) {
      $keys[0] = $target;

      foreach (['administer foo', FALSE] as $admin_permission) {
        $keys[1] = $admin_permission ? 'admin' : 'noadmin';

        foreach (['any some permission name', FALSE] as $any_permission) {
          $keys[2] = $any_permission ? 'anyperm' : 'noanyperm';

          foreach (['own some permission name', FALSE] as $own_permission) {
            $keys[3] = $own_permission ? 'ownperm' : 'noownperm';

            foreach ([TRUE, FALSE] as $is_ownable) {
              $keys[4] = $is_ownable ? 'ownable' : 'noownable';

              foreach ([TRUE, FALSE] as $is_publishable) {
                $keys[5] = $is_publishable ? 'pub' : 'nopub';

                if ($target === 'relationship') {
                  $expected = $any_permission || $own_permission;
                }
                else {
                  $expected = $any_permission !== FALSE;
                  if (!$expected && $is_ownable) {
                    $expected = $own_permission !== FALSE;
                  }
                }

                $case = [
                  'expected' => $expected,
                  // We use a derivative ID to prove these work.
                  'plugin_id' => 'foo:baz',
                  'definition' => new GroupRelationType([
                    'id' => 'foo',
                    'label' => 'Foo',
                    'entity_type_id' => 'bar',
                    'admin_permission' => $admin_permission,
                  ]),
                  'operation' => 'some operation',
                  'target' => $target,
                  'any_permission' => $any_permission,
                  'own_permission' => $own_permission,
                  'is_ownable' => $is_ownable,
                  'is_publishable' => $is_publishable,
                ];

                yield implode('-', $keys) => $case;
              }
            }
          }
        }
      }
    }
  }

  /**
   * Tests the relation operation access.
   *
   * @param \Closure $expected
   *   A closure returning the expected access result.
   * @param string $plugin_id
   *   The plugin ID.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $definition
   *   The plugin definition.
   * @param bool $has_admin_permission
   *   Whether the account has the admin permission.
   * @param bool $has_permission
   *   Whether the account has the required permission.
   * @param bool $has_own_permission
   *   Whether the account has the required owner permission.
   * @param string|false $permission
   *   The operation permission.
   * @param string|false $own_permission
   *   The owner operation permission.
   * @param bool $is_owner
   *   Whether the account owns the relation.
   * @param bool $check_chain
   *   Whether to check the override that supports all operations.
   *
   * @covers ::relationshipAccess
   * @dataProvider relationshipAccessProvider
   */
  public function testRelationshipAccess(\Closure $expected, $plugin_id, GroupRelationTypeInterface $definition, $has_admin_permission, $has_permission, $has_own_permission, $permission, $own_permission, $is_owner, $check_chain) {
    $operation = $this->randomMachineName();

    $permission_provider = $this->prophesize(PermissionProviderInterface::class);
    $permission_provider->getAdminPermission()->willReturn($definition->getAdminPermission());
    $permission_provider->getPermission($operation, 'relationship', 'any')->willReturn($permission);
    $permission_provider->getPermission($operation, 'relationship', 'own')->willReturn($own_permission);
    $access_control_handler = $this->createAccessControlHandler($plugin_id, $definition, $permission_provider->reveal(), NULL, $check_chain);

    $account_id = rand(1, 100);
    $account = $this->prophesize(AccountInterface::class);
    $account->id()->willReturn($account_id);
    $account = $account->reveal();

    $group = $this->prophesize(GroupInterface::class);
    $group_relationship = $this->prophesize(GroupRelationshipInterface::class);
    $group_relationship->getGroup()->willReturn($group->reveal());
    $group_relationship->getOwnerId()->willReturn($is_owner ? $account_id : $account_id + 1);
    $group_relationship->getCacheContexts()->willReturn([]);
    $group_relationship->getCachetags()->willReturn(['group_relationship:foo']);
    $group_relationship->getCacheMaxAge()->willReturn(9999);

    $is_supported = $permission || $own_permission || $check_chain;
    if ($definition->getAdminPermission() && $is_supported) {
      $group->hasPermission($definition->getAdminPermission(), $account)->willReturn($has_admin_permission);
    }
    else {
      $group->hasPermission($definition->getAdminPermission(), $account)->shouldNotBeCalled();
    }

    if ($permission) {
      $group->hasPermission($permission, $account)->willReturn($has_permission);
    }
    else {
      $group->hasPermission($permission, $account)->shouldNotBeCalled();
    }

    if ($own_permission && $is_owner) {
      $group->hasPermission($own_permission, $account)->willReturn($has_own_permission);
    }
    else {
      $group->hasPermission($own_permission, $account)->shouldNotBeCalled();
    }

    $result = $access_control_handler->relationshipAccess($group_relationship->reveal(), $operation, $account, TRUE);
    $this->assertEquals($expected(), $result);
  }

  /**
   * Data provider for testRelationshipAccess().
   *
   * @return array
   *   A list of testRelationshipAccess method arguments.
   */
  public function relationshipAccessProvider() {
    $cases = [];

    foreach ($this->getAccessControlHandlerScenarios() as $key => $scenario) {
      $keys[0] = $key;

      foreach (['any some permission name', FALSE] as $any_permission) {
        $keys[1] = $any_permission ? 'anyperm' : 'noanyperm';

        foreach (['own some permission name', FALSE] as $own_permission) {
          $keys[2] = $own_permission ? 'ownperm' : 'noownperm';

          foreach ([TRUE, FALSE] as $has_own_permission) {
            $keys[3] = $has_own_permission ? 'hasown' : 'nohasown';

            foreach ([TRUE, FALSE] as $is_owner) {
              $keys[4] = $is_owner ? 'isowner' : 'noisowner';

              foreach ([TRUE, FALSE] as $check_chain) {
                $keys[5] = $check_chain ? 'chain' : 'nochain';

                $case = $scenario;
                $case['definition'] = clone $scenario['definition'];

                // Default is neutral result if no permissions are defined.
                $case['expected'] = function() {
                  return AccessResult::neutral();
                };

                $admin_permission = $case['definition']->getAdminPermission();
                if ($any_permission || $own_permission || $check_chain) {
                  $has_admin = $admin_permission && $case['has_admin_permission'];
                  $has_any = $any_permission && $case['has_permission'];
                  $has_own = $is_owner && $own_permission && $has_own_permission;

                  $permissions_were_checked = $admin_permission || $any_permission || ($is_owner && $own_permission);
                  $case['expected'] = function() use ($has_admin, $has_any, $has_own, $permissions_were_checked, $own_permission) {
                    $result = AccessResult::allowedIf($has_admin || $has_any || $has_own);

                    // Only add the permissions context if they were checked.
                    if ($permissions_were_checked) {
                      $result->addCacheContexts(['user.group_permissions']);
                    }

                    // Add the user context and the relation's cache metadata if
                    // we're dealing with an owner permission.
                    if ($own_permission) {
                      $result->addCacheContexts(['user']);

                    // Tags and max-age as defined in ::testRelationAccess().
                    $result->addCacheTags(['group_relationship:foo']);
                    $result->mergeCacheMaxAge(9999);
                  }
                  return $result;
                };
              }

                $case['has_own_permission'] = $has_own_permission;
                $case['any_permission'] = $any_permission;
                $case['own_permission'] = $own_permission;
                $case['is_owner'] = $is_owner;
                $case['check_chain'] = $check_chain;
                $cases[implode('-', $keys)] = $case;
              }
            }
          }
        }
      }
    }

    return $cases;
  }

  /**
   * Tests the relation create access.
   *
   * @param \Closure $expected
   *   A closure returning the expected access result.
   * @param string $plugin_id
   *   The plugin ID.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $definition
   *   The plugin definition.
   * @param bool $has_admin_permission
   *   Whether the account has the admin permission.
   * @param bool $has_permission
   *   Whether the account has the required permission.
   * @param string|false $permission
   *   The relation create permission.
   * @param bool $check_chain
   *   Whether to check the override that supports all operations.
   *
   * @covers ::relationshipCreateAccess
   * @dataProvider relationshipCreateAccessProvider
   */
  public function testRelationshipCreateAccess(\Closure $expected, $plugin_id, GroupRelationTypeInterface $definition, $has_admin_permission, $has_permission, $permission, $check_chain) {
    $permission_provider = $this->prophesize(PermissionProviderInterface::class);
    $permission_provider->getAdminPermission()->willReturn($definition->getAdminPermission());
    $permission_provider->getPermission('create', 'relationship', Argument::cetera())->willReturn($permission);
    $access_control_handler = $this->createAccessControlHandler($plugin_id, $definition, $permission_provider->reveal(), NULL, $check_chain);

    $group = $this->prophesize(GroupInterface::class);
    $account = $this->prophesize(AccountInterface::class)->reveal();

    $is_supported = $permission || $check_chain;
    if ($definition->getAdminPermission() && $is_supported) {
      $group->hasPermission($definition->getAdminPermission(), $account)->willReturn($has_admin_permission);
    }
    else {
      $group->hasPermission($definition->getAdminPermission(), $account)->shouldNotBeCalled();
    }

    if ($permission) {
      $group->hasPermission($permission, $account)->willReturn($has_permission);
    }
    else {
      $group->hasPermission($permission, $account)->shouldNotBeCalled();
    }

    $result = $access_control_handler->relationshipCreateAccess($group->reveal(), $account, TRUE);
    $this->assertEquals($expected(), $result);
  }

  /**
   * Data provider for testRelationshipCreateAccess.
   *
   * @return array
   *   A list of testRelationshipCreateAccess method arguments.
   */
  public function relationshipCreateAccessProvider() {
    $cases = [];

    foreach ($this->getAccessControlHandlerScenarios() as $key => $scenario) {
      $keys[0] = $key;

      foreach (['some permission name', FALSE] as $permission) {
        $keys[1] = $permission ? 'perm' : 'noperm';

        foreach ([TRUE, FALSE] as $check_chain) {
          $keys[2] = $check_chain ? 'chain' : 'nochain';

          $case = $scenario;
          $case['definition'] = clone $scenario['definition'];

          // Default is neutral result if no permissions are defined or entity
          // access control is turned off for the plugin.
          $case['expected'] = function() {
            return AccessResult::neutral();
          };

          if ($permission || $check_chain) {
            $has_admin = $case['definition']->getAdminPermission() && $case['has_admin_permission'];
            $has_regular = $permission && $case['has_permission'];

            $permissions_were_checked = $case['definition']->getAdminPermission() || $permission;
            $case['expected'] = function() use ($has_admin, $has_regular, $permissions_were_checked) {
              $result = AccessResult::allowedIf($has_admin || $has_regular);
              if ($permissions_were_checked) {
                $result->addCacheContexts(['user.group_permissions']);
              }
              return $result;
            };
          }

          $case['permission'] = $permission;
          $case['check_chain'] = $check_chain;
          $cases[implode('-', $keys)] = $case;
        }
      }
    }

    return $cases;
  }

  /**
   * Tests the entity operation access.
   *
   * @param \Closure $expected
   *   A closure returning the expected access result.
   * @param string $plugin_id
   *   The plugin ID.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $definition
   *   The plugin definition.
   * @param bool $has_admin_permission
   *   Whether the account has the admin permission.
   * @param bool $has_permission
   *   Whether the account has the required permission.
   * @param bool $has_own_permission
   *   Whether the account has the required owner permission.
   * @param string|false $permission
   *   The operation permission.
   * @param string|false $own_permission
   *   The owner operation permission.
   * @param bool $is_grouped
   *   Whether the entity is grouped.
   * @param bool $is_ownable
   *   Whether the entity can be owned.
   * @param bool $is_owner
   *   Whether the account owns the entity.
   * @param bool $is_publishable
   *   Whether the entity can be (un)published.
   * @param bool $is_published
   *   Whether the entity is be published.
   * @param string $operation
   *   The operation to check access for.
   * @param bool $check_chain
   *   Whether to check the override that supports all operations.
   *
   * @covers ::entityAccess
   * @dataProvider entityAccessProvider
   */
  public function testEntityAccess(\Closure $expected, $plugin_id, GroupRelationTypeInterface $definition, $has_admin_permission, $has_permission, $has_own_permission, $permission, $own_permission, $is_grouped, $is_ownable, $is_owner, $is_publishable, $is_published, $operation, $check_chain) {
    $storage = $this->prophesize(GroupRelationshipStorageInterface::class);
    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->entityClassImplements(EntityPublishedInterface::class)->willReturn($is_publishable);
    $entity_type->entityClassImplements(EntityOwnerInterface::class)->willReturn($is_ownable);

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('group_relationship')->willReturn($storage->reveal());
    $entity_type_manager->getDefinition($definition->getEntityTypeId())->willReturn($entity_type->reveal());

    $permission_provider = $this->prophesize(PermissionProviderInterface::class);
    $permission_provider->getAdminPermission()->willReturn($definition->getAdminPermission());

    $check_published = $operation === 'view' && $is_publishable;
    if ($check_published && !$is_published) {
      $permission_provider->getPermission("$operation unpublished", 'entity', 'any')->willReturn($permission);
      $permission_provider->getPermission("$operation unpublished", 'entity', 'own')->willReturn($own_permission);
    }
    else {
      $permission_provider->getPermission($operation, 'entity', 'any')->willReturn($permission);
      $permission_provider->getPermission($operation, 'entity', 'own')->willReturn($own_permission);
    }
    $access_control_handler = $this->createAccessControlHandler($plugin_id, $definition, $permission_provider->reveal(), $entity_type_manager->reveal(), $check_chain);

    $account_id = rand(1, 100);
    $account = $this->prophesize(AccountInterface::class);
    $account->id()->willReturn($account_id);
    $account = $account->reveal();

    $entity = $this->prophesize(ContentEntityInterface::class);
    $entity->willImplement(EntityOwnerInterface::class);
    if ($is_publishable) {
      $entity->willImplement(EntityPublishedInterface::class);
      $entity->isPublished()->willReturn($is_published);
    }
    $entity->getOwnerId()->willReturn($is_owner ? $account_id : $account_id + 1);
    $entity->getCacheContexts()->willReturn([]);
    $entity->getCachetags()->willReturn(['some_entity:foo']);
    $entity->getCacheMaxAge()->willReturn(9999);
    $entity = $entity->reveal();

    if (!$is_grouped) {
      $storage->loadByEntity($entity, $plugin_id)->willReturn([]);
    }
    else {
      $group = $this->prophesize(GroupInterface::class);
      $group_relationship = $this->prophesize(GroupRelationshipInterface::class);
      $group_relationship->getGroup()->willReturn($group->reveal());
      $group_relationship->getPluginId()->willReturn('foo:baz');
      $group_relationship = $group_relationship->reveal();

      $group_relationship_2 = $this->prophesize(GroupRelationshipInterface::class);
      $group_relationship_2->getGroup()->willReturn($group->reveal());
      $group_relationship_2->getPluginId()->willReturn('cat:dog');
      $group_relationship_2 = $group_relationship_2->reveal();

      $storage->loadByEntity($entity, $plugin_id)->willReturn([1 => $group_relationship, 2 => $group_relationship_2]);

      $is_supported = $permission || ($own_permission && $is_ownable) || $check_chain;
      if ($definition->getAdminPermission() && $is_supported) {
        $group->hasPermission($definition->getAdminPermission(), $account)->willReturn($has_admin_permission);
      }
      else {
        $group->hasPermission($definition->getAdminPermission(), $account)->shouldNotBeCalled();
      }

      $checked_and_found_admin = $definition->getAdminPermission() && $is_supported && $has_admin_permission;
      if ($permission && !$checked_and_found_admin) {
        $group->hasPermission($permission, $account)->willReturn($has_permission);
      }
      else {
        $group->hasPermission($permission, $account)->shouldNotBeCalled();
      }

      $checked_and_found_any = $permission && $has_permission;
      if ($own_permission && $is_owner && !$checked_and_found_admin && !$checked_and_found_any) {
        $group->hasPermission($own_permission, $account)->willReturn($has_own_permission);
      }
      else {
        $group->hasPermission($own_permission, $account)->shouldNotBeCalled();
      }
    }

    $result = $access_control_handler->entityAccess($entity, $operation, $account, TRUE);
    $this->assertEqualsCanonicalizing($expected(), $result);
  }

  /**
   * Data provider for testEntityAccess().
   *
   * @return array
   *   A list of testEntityAccess method arguments.
   */
  public function entityAccessProvider() {
    foreach ($this->getAccessControlHandlerScenarios() as $key => $scenario) {
      $keys[0] = $key;

      foreach (['any some permission name', FALSE] as $any_permission) {
        $keys[1] = $any_permission ? 'anyperm' : 'noanyperm';

        foreach (['own some permission name', FALSE] as $own_permission) {
          $keys[2] = $own_permission ? 'ownperm' : 'noownperm';

          foreach ([TRUE, FALSE] as $has_own_permission) {
            $keys[3] = $has_own_permission ? 'hasown' : 'nohasown';

            foreach ([TRUE, FALSE] as $is_grouped) {
              $keys[4] = $is_grouped ? 'grouped' : 'nogrouped';

              foreach ([TRUE, FALSE] as $is_ownable) {
                $keys[5] = $is_ownable ? 'ownable' : 'noownable';

                foreach ([TRUE, FALSE] as $is_owner) {
                  $keys[6] = $is_owner ? 'isowner' : 'noisowner';

                  foreach ([TRUE, FALSE] as $is_publishable) {
                    $keys[7] = $is_publishable ? 'pub' : 'nopub';

                    foreach ([TRUE, FALSE] as $is_published) {
                      $keys[8] = $is_published ? 'ispub' : 'noispub';

                      foreach (['view', $this->randomMachineName()] as $operation) {
                        $keys[9] = $operation === 'view' ? 'opview' : 'noopview';

                        foreach ([TRUE, FALSE] as $check_chain) {
                          $keys[10] = $check_chain ? 'chain' : 'nochain';

                          $case = $scenario;
                          $case['definition'] = clone $scenario['definition'];

                          $is_supported = $check_chain || $any_permission !== FALSE;
                          if (!$is_supported && $is_ownable) {
                            $is_supported = $own_permission !== FALSE;
                          }

                          if (!$is_supported) {
                            $case['expected'] = function() {
                              return AccessResult::neutral();
                            };
                          }
                          else {
                            $check_published = $operation === 'view' && $is_publishable;

                            $admin_permission = $case['definition']->getAdminPermission();
                            $permissions_were_checked = $admin_permission || $any_permission || ($is_owner && $own_permission && $is_ownable);

                            // Default varies on whether the entity is grouped.
                            $case['expected'] = function() use ($is_grouped, $own_permission, $check_published, $permissions_were_checked) {
                              $result = AccessResult::forbiddenIf($is_grouped);
                              if ($is_grouped) {
                                if ($permissions_were_checked) {
                                  $result->addCacheContexts(['user.group_permissions']);
                                }

                                if ($own_permission) {
                                  $result->addCacheContexts(['user']);
                                }

                                if ($own_permission || $check_published) {
                                  $result->addCacheTags(['some_entity:foo']);
                                  $result->mergeCacheMaxAge(9999);
                                }
                              }
                              return $result;
                            };

                            if ($is_grouped && ($any_permission || $own_permission || $check_chain)) {
                              $admin_access = $admin_permission && $case['has_admin_permission'];

                              if (!$check_published || $is_published) {
                                $any_access = $any_permission && $case['has_permission'];
                                $own_access = $is_ownable && $is_owner && $own_permission && $has_own_permission;
                              }
                              elseif ($check_published && !$is_published) {
                                $any_access = $any_permission && $case['has_permission'];
                                $own_access = $is_ownable && $is_owner && $own_permission && $has_own_permission;
                              }
                              else {
                                $any_access = FALSE;
                                $own_access = FALSE;
                              }

                              $case['expected'] = function() use ($admin_access, $any_access, $own_access, $own_permission, $check_published, $permissions_were_checked) {
                                $result = AccessResult::allowedIf($admin_access || $any_access || $own_access);

                                if (!$result->isAllowed()) {
                                  $result = AccessResult::forbidden();
                                }

                                if ($own_permission) {
                                  $result->addCacheContexts(['user']);
                                }

                                if ($own_permission || $check_published) {
                                  $result->addCacheTags(['some_entity:foo']);
                                  $result->mergeCacheMaxAge(9999);
                                }

                                if ($permissions_were_checked) {
                                  $result->addCacheContexts(['user.group_permissions']);
                                }

                                return $result;
                              };
                            }
                          }

                          $case['has_own_permission'] = $has_own_permission;
                          $case['any_permission'] = $any_permission;
                          $case['own_permission'] = $own_permission;
                          $case['is_grouped'] = $is_grouped;
                          $case['is_ownable'] = $is_ownable;
                          $case['is_owner'] = $is_owner;
                          $case['is_publishable'] = $is_publishable;
                          $case['is_published'] = $is_published;
                          $case['operation'] = $operation;
                          $case['check_chain'] = $check_chain;
                          yield implode('-', $keys) => $case;
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }

  /**
   * Tests the entity create access.
   *
   * @param \Closure $expected
   *   A closure returning the expected access result.
   * @param string $plugin_id
   *   The plugin ID.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $definition
   *   The plugin definition.
   * @param bool $has_admin_permission
   *   Whether the account has the admin permission.
   * @param bool $has_permission
   *   Whether the account has the required permission.
   * @param string|false $permission
   *   The entity create permission.
   * @param bool $check_chain
   *   Whether to check the override that supports all operations.
   *
   * @covers ::entityCreateAccess
   * @dataProvider entityCreateAccessProvider
   */
  public function testEntityCreateAccess(\Closure $expected, $plugin_id, GroupRelationTypeInterface $definition, $has_admin_permission, $has_permission, $permission, $check_chain) {
    $permission_provider = $this->prophesize(PermissionProviderInterface::class);
    $permission_provider->getAdminPermission()->willReturn($definition->getAdminPermission());
    $permission_provider->getPermission('create', 'entity', Argument::cetera())->willReturn($permission);
    $access_control_handler = $this->createAccessControlHandler($plugin_id, $definition, $permission_provider->reveal(), NULL, $check_chain);

    $group = $this->prophesize(GroupInterface::class);
    $account = $this->prophesize(AccountInterface::class)->reveal();

    $is_supported = $permission || $check_chain;
    if ($definition->getAdminPermission() && $is_supported) {
      $group->hasPermission($definition->getAdminPermission(), $account)->willReturn($has_admin_permission);
    }
    else {
      $group->hasPermission($definition->getAdminPermission(), $account)->shouldNotBeCalled();
    }

    if ($permission) {
      $group->hasPermission($permission, $account)->willReturn($has_permission);
    }
    else {
      $group->hasPermission($permission, $account)->shouldNotBeCalled();
    }

    $result = $access_control_handler->entityCreateAccess($group->reveal(), $account, TRUE);
    $this->assertEquals($expected(), $result);
  }

  /**
   * Data provider for entityCreateAccessProvider.
   *
   * @return array
   *   A list of entityCreateAccessProvider method arguments.
   */
  public function entityCreateAccessProvider() {
    $cases = [];

    foreach ($this->getAccessControlHandlerScenarios() as $key => $scenario) {
      $keys[0] = $key;

      foreach (['some permission name', FALSE] as $permission) {
        $keys[1] = $permission ? 'perm' : 'noperm';

        foreach ([TRUE, FALSE] as $check_chain) {
          $keys[2] = $check_chain ? 'chain' : 'nochain';

          $case = $scenario;
          $case['definition'] = clone $scenario['definition'];

          // Default is neutral result if no permissions are defined or entity
          // access control is turned off for the plugin.
          $case['expected'] = function() {
            return AccessResult::neutral();
          };

          if ($permission || $check_chain) {
            $has_admin = $case['definition']->getAdminPermission() && $case['has_admin_permission'];
            $has_regular = $permission && $case['has_permission'];

            $permissions_were_checked = $case['definition']->getAdminPermission() || $permission;
            $case['expected'] = function() use ($has_admin, $has_regular, $permissions_were_checked) {
              $result = AccessResult::allowedIf($has_admin || $has_regular);
              if ($permissions_were_checked) {
                $result->addCacheContexts(['user.group_permissions']);
              }
              return $result;
            };
          }

          $case['permission'] = $permission;
          $case['check_chain'] = $check_chain;
          $cases[implode('-', $keys)] = $case;
        }
      }
    }

    return $cases;
  }

  /**
   * All possible scenarios for an access control handler.
   *
   * @return array
   *   A set of test cases to be used in data providers.
   */
  protected function getAccessControlHandlerScenarios() {
    $scenarios = [];

    foreach (['administer foo', FALSE] as $admin_permission) {
      $keys[0] = $admin_permission ? 'admin' : 'noadmin';

      foreach ([TRUE, FALSE] as $has_admin_permission) {
        $keys[1] = $has_admin_permission ? 'hasadmin' : 'nohasadmin';

        foreach ([TRUE, FALSE] as $has_permission) {
          $keys[2] = $has_permission ? 'hasperm' : 'nohasperm';

          $scenarios[implode('-', $keys)] = [
            'expected' => NULL,
            // We use a derivative ID to prove these work.
            'plugin_id' => 'foo:baz',
            'definition' => new GroupRelationType([
              'id' => 'foo',
              'label' => 'Foo',
              'entity_type_id' => 'bar',
              'admin_permission' => $admin_permission,
            ]),
            'has_admin_permission' => $has_admin_permission,
            'has_permission' => $has_permission,
          ];
        }
      }
    }

    return $scenarios;
  }

  /**
   * Instantiates a default access control handler.
   *
   * @return \Drupal\group\Plugin\Group\RelationHandlerDefault\AccessControl
   *   The default access control handler.
   */
  protected function createAccessControlHandler(
    $plugin_id,
    $definition,
    PermissionProviderInterface $permission_provider,
    EntityTypeManagerInterface $entity_type_manager = NULL,
    $set_up_chain = FALSE
  ) {
    $this->assertNotEmpty($definition->getEntityTypeId());

    if (!isset($entity_type_manager)) {
      $entity_type = $this->prophesize(EntityTypeInterface::class);
      $entity_type->entityClassImplements(Argument::any())->willReturn(FALSE);
      $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
      $entity_type_manager->getDefinition($definition->getEntityTypeId())->willReturn($entity_type->reveal());
      $entity_type_manager = $entity_type_manager->reveal();
    }

    $relation_type_manager = $this->prophesize(GroupRelationTypeManagerInterface::class);
    $relation_type_manager->getPermissionProvider($plugin_id)->willReturn($permission_provider);

    $access_control = new AccessControl($entity_type_manager, $relation_type_manager->reveal());
    $access_control->init($plugin_id, $definition);

    $chained = $access_control;
    if ($set_up_chain) {
      $chained = new TestAccessControlWithFullOperationSupport($access_control, $entity_type_manager, $relation_type_manager->reveal());
      $chained->init($plugin_id, $definition);
    }
    $relation_type_manager->getAccessControlHandler($plugin_id)->willReturn($chained);

    return $access_control;
  }

}

class TestAccessControlWithFullOperationSupport implements AccessControlInterface {

  use AccessControlTrait;

  public function __construct(AccessControlInterface $parent, EntityTypeManagerInterface $entity_type_manager, GroupRelationTypeManagerInterface $relation_type_manager) {
    $this->parent = $parent;
    $this->entityTypeManager = $entity_type_manager;
    $this->groupRelationTypeManager = $relation_type_manager;
  }

  public function supportsOperation($operation, $target) {
    return TRUE;
  }

}
