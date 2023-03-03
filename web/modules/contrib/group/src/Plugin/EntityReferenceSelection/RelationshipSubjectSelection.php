<?php

namespace Drupal\group\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Shows wrapper config entities rather than config wrappers.
 *
 * @EntityReferenceSelection(
 *   id = "group_config_wrapper:target_entity",
 *   label = @Translation("Group relationship target selection"),
 *   entity_types = {"group_config_wrapper"},
 *   group = "group_config_wrapper",
 *   weight = 0
 * )
 */
class RelationshipSubjectSelection extends DefaultSelection {

  /**
   * Disables the swapping mechanism.
   *
   * @var bool
   */
  protected $swapKillSwitch = FALSE;

  /**
   * Keeps track of whether we swapped out configuration.
   *
   * @var bool
   */
  protected $configSwapped = FALSE;

  /**
   * Way to keep track of the original config.
   *
   * @var array
   */
  protected $configOriginal;

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $this->swapConfiguration(TRUE);
    $options = parent::getReferenceableEntities($match, $match_operator, $limit);
    $this->swapConfiguration(FALSE);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableEntities(array $ids) {
    $result_sets = [];

    // This might be called while we are dealing with the swapped entities, but
    // also when validating a form after we've already wrapped the entity.
    foreach ([TRUE, FALSE] as $activate_kill_switch) {
      $this->swapKillSwitch = $activate_kill_switch;
      $this->swapConfiguration(TRUE);
      $result_sets[] = parent::validateReferenceableEntities($ids);
      $this->swapConfiguration(FALSE);
    }

    return array_merge(...$result_sets);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $this->swapConfiguration(TRUE);
    $query = parent::buildEntityQuery($match, $match_operator);
    $this->swapConfiguration(FALSE);
    return $query;
  }

  /**
   * Tricks the field into thinking it targets the config entity type.
   *
   * @param bool $swap_out
   *   Swap out the config for the fake one (TRUE) or swap back (FALSE).
   */
  protected function swapConfiguration($swap_out) {
    if ($this->swapKillSwitch) {
      return;
    }

    if (!$this->configSwapped) {
      if ($swap_out) {
        $this->configOriginal = $modified = $this->getConfiguration();

        $modified['target_type'] = reset($modified['target_bundles']);
        unset($modified['target_bundles']);
        $this->setConfiguration($modified);

        $this->configSwapped = TRUE;
      }
    }
    elseif(!$swap_out) {
      $this->setConfiguration($this->configOriginal);
      $this->configSwapped = FALSE;
    }
  }

}
