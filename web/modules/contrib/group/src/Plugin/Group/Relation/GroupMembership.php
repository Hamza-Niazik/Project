<?php

namespace Drupal\group\Plugin\Group\Relation;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a group relation for users as members.
 *
 * @GroupRelationType(
 *   id = "group_membership",
 *   label = @Translation("Group membership"),
 *   description = @Translation("Adds users to groups as members."),
 *   entity_type_id = "user",
 *   pretty_path_key = "member",
 *   reference_label = @Translation("User"),
 *   reference_description = @Translation("The user you want to make a member"),
 *   enforced = TRUE,
 *   admin_permission = "administer members"
 * )
 */
class GroupMembership extends GroupRelationBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['entity_cardinality'] = 1;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Disable the entity cardinality field as the functionality of this module
    // relies on a cardinality of 1. We don't just hide it, though, to keep a UI
    // that's consistent with other group relations.
    $info = $this->t("This field has been disabled by the plugin to guarantee the functionality that's expected of it.");
    $form['entity_cardinality']['#disabled'] = TRUE;
    $form['entity_cardinality']['#description'] .= '<br /><em>' . $info . '</em>';

    return $form;
  }

}
