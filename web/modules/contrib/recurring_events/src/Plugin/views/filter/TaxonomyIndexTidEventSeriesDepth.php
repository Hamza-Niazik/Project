<?php

namespace Drupal\recurring_events\Plugin\views\filter;

use \Drupal\Core\Database\Query\Condition;
use \Drupal\Core\Entity\EntityFieldManagerInterface;
use \Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use \Drupal\Core\Form\FormStateInterface;
use \Drupal\Core\Session\AccountInterface;
use \Drupal\field\Entity\FieldConfig;
use \Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid;
use \Drupal\taxonomy\TermStorageInterface;
use \Drupal\taxonomy\VocabularyStorageInterface;
use \Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter handler for taxonomy terms with depth.
 *
 * This handler is actually part of the node table and has some restrictions,
 * because it uses a subquery to find nodes with.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("taxonomy_index_tid_eventseries_depth")
 */
class TaxonomyIndexTidEventSeriesDepth extends TaxonomyIndexTid {

  /**
   * The entity bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   */
  protected $entityFieldManager;

  /**
   * The entity type.
   *
   * @var \string
   */
  protected $entityType = 'eventseries';

  /**
   * The entity type label.
   *
   * @var \string
   */
  protected $entityTypeLabel = 'Event Series';

  /**
   * Constructs a TaxonomyIndexTid object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\taxonomy\VocabularyStorageInterface $vocabulary_storage
   *   The vocabulary storage.
   * @param \Drupal\taxonomy\TermStorageInterface $term_storage
   *   The term storage.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, VocabularyStorageInterface $vocabulary_storage, TermStorageInterface $term_storage, AccountInterface $current_user = NULL,EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, EntityFieldManagerInterface $entity_field_manager = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $vocabulary_storage, $term_storage, $current_user);
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('taxonomy_vocabulary'),
      $container->get('entity_type.manager')->getStorage('taxonomy_term'),
      $container->get('current_user'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function operatorOptions($which = 'title') {
    return [
      'or' => $this->t('Is one of'),
    ];
  }

  /**
   * {@inheritDoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['reference_field'] = ['default' => FALSE];
    $options['depth'] = ['default' => 0];
    return $options;
  }

  /**
   * {@inheritDoc}
   */
  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildExtraOptionsForm($form, $form_state);

    $bundles = array_keys($this->entityTypeBundleInfo->getBundleInfo($this->entityType));
    $fields = [];
    foreach ($bundles as $bundle) {
      $bundle_fields = $this->entityFieldManager->getFieldDefinitions($this->entityType, $bundle);
      foreach ($bundle_fields as $name => $field) {
        if ($field instanceof FieldConfig) {
          if ($field->getType() === 'entity_reference') {
            $fields[$name] = $name;
          }
        }
      }
    }

    $form['reference_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Reference field'),
      '#default_value' => $this->options['reference_field'],
      '#description' => $this->t('The field name (machine name) in the product type, which is referencing to a taxonomy. For example field_product_category.'),
      '#options' => $fields,
    ];

    $form['depth'] = [
      '#type' => 'weight',
      '#title' => $this->t('Depth'),
      '#default_value' => $this->options['depth'],
      '#description' => $this->t('The depth will match nodes tagged with terms in the hierarchy. For example, if you have the term "fruit" and a child term "apple", with a depth of 1 (or higher) then filtering for the term "fruit" will get nodes that are tagged with "apple" as well as "fruit". If negative, the reverse is true; searching for "apple" will also pick up nodes tagged with "fruit" if depth is -1 (or lower).'),
    ];
  }

  public function query() {

    // Get the DB table and reference column name from the reference field name.
    $ref_field_name = $this->options['reference_field'] . '_target_id';
    $ref_table_name = $this->entityType . '__' . $this->options['reference_field'];

    // If no filter values are present, then do nothing.
    if (count($this->value) == 0) {
      return;
    }
    elseif (count($this->value) == 1) {
      // Sometimes $this->value is an array with a single element so convert it.
      if (is_array($this->value)) {
        $this->value = current($this->value);
      }
      $operator = '=';
    }
    else {
      $operator = 'IN';
    }

    // The normal use of ensureMyTable() here breaks Views.
    // So instead we trick the filter into using the alias of the base table.
    //   See https://www.drupal.org/node/271833.
    // If a relationship is set, we must use the alias it provides.
    if (!empty($this->relationship)) {
      $this->tableAlias = $this->relationship;
    }
    // If no relationship, then use the alias of the base table.
    else {
      $this->tableAlias = $this->query->ensureTable($this->view->storage->get('base_table'));
    }

    // Now build the subqueries.
    $subquery = \Drupal::database()->select($ref_table_name, 'tn');
    $subquery->addField('tn', 'entity_id');
    $where = new Condition('OR');
    $where->condition('tn.' . $ref_field_name, $this->value, $operator);
    $last = "tn";

    if ($this->options['depth'] > 0) {
      $subquery->leftJoin('taxonomy_term__parent', 'tp', "tp.entity_id = tn." . $ref_field_name);
      $last = "tp";
      foreach (range(1, abs($this->options['depth'])) as $count) {
        $subquery->leftJoin('taxonomy_term__parent', "tp$count", "$last.parent_target_id = tp$count.entity_id");
        $where->condition("tp$count.entity_id", $this->value, $operator);
        $last = "tp$count";
      }
    }
    elseif ($this->options['depth'] < 0) {
      foreach (range(1, abs($this->options['depth'])) as $count) {
        $subquery->leftJoin('taxonomy_term__parent', "tp$count", "$last.entity_id = tp$count.parent_target_id");
        $where->condition("tp$count.entity_id", $this->value, $operator);
        $last = "tp$count";
      }
    }

    $subquery->condition($where);
    $this->query->addWhere($this->options['group'], "$this->tableAlias.$this->realField", $subquery, 'IN');
  }

}
