<?php

namespace Drupal\Tests\group\Functional;

/**
 * Tests the group creator wizard.
 *
 * @group group
 */
class GroupCreatorWizardTest extends GroupBrowserTestBase {

  /**
   * Tests that a group creator gets a membership using the wizard.
   */
  public function testCreatorMembershipWizard() {
    $group_type = $this->createGroupType();
    $group_type_id = $group_type->id();

    $role = $this->drupalCreateRole(["create $group_type_id group"]);
    $this->groupCreator->addRole($role);
    $this->groupCreator->save();

    $this->drupalGet("/group/add/$group_type_id");
    $this->assertSession()->statusCodeEquals(200);

    $submit_button = 'Create ' . $group_type->label() . ' and complete your membership';
    $this->assertSession()->buttonExists($submit_button);
    $this->assertSession()->buttonExists('Cancel');

    $edit = ['Title' => $this->randomString()];
    $this->submitForm($edit, $submit_button);

    $submit_button = 'Save group and membership';
    $this->assertSession()->buttonExists($submit_button);
    $this->assertSession()->buttonExists('Back');
  }

  /**
   * Tests that a group creator gets a membership without using the wizard.
   */
  public function testCreatorMembershipNoWizard() {
    $group_type = $this->createGroupType(['creator_wizard' => FALSE]);
    $group_type_id = $group_type->id();

    $role = $this->drupalCreateRole(["create $group_type_id group"]);
    $this->groupCreator->addRole($role);
    $this->groupCreator->save();

    $this->drupalGet("/group/add/$group_type_id");
    $this->assertSession()->statusCodeEquals(200);

    $submit_button = 'Create ' . $group_type->label() . ' and become a member';
    $this->assertSession()->buttonExists($submit_button);
    $this->assertSession()->buttonNotExists('Cancel');
  }

  /**
   * Tests that a group form is not turned into a wizard.
   */
  public function testNoWizard() {
    $group_type = $this->createGroupType([
      'creator_membership' => FALSE,
      'creator_wizard' => FALSE,
    ]);
    $group_type_id = $group_type->id();

    $role = $this->drupalCreateRole(["create $group_type_id group"]);
    $this->groupCreator->addRole($role);
    $this->groupCreator->save();

    $this->drupalGet("/group/add/$group_type_id");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->buttonExists('Create ' . $group_type->label());
    $this->assertSession()->buttonNotExists('Cancel');
  }

}
