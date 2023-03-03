<?php

namespace Drupal\recurring_events\Plugin\views\argument;

use \Drupal\Core\Database\Query\Condition;
use \Drupal\Core\Entity\EntityFieldManagerInterface;
use \Drupal\Core\Entity\EntityStorageInterface;
use \Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use \Drupal\Core\Form\FormStateInterface;
use \Drupal\field\Entity\FieldConfig;
use \Drupal\taxonomy\Plugin\views\argument\IndexTidDepth;
use \Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler for event series with taxonomy terms with depth.
 *
 * Normally taxonomy terms with depth contextual filter can be used
 * only for content. This handler can be used for Recurring Events series.
 *
 * Handler expects reference field name, gets reference table and column and
 * builds sub query on that table. That is why handler does not need special
 * relation table like taxonomy_index.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("taxonomy_index_tid_eventseries_depth")
 */
class IndexTidEventSeriesDepth extends IndexTidDepth {

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
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $term_storage
   *   The entity storage interface.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $term_storage, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $term_storage);
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
      $container->get('entity_type.manager')->getStorage('taxonomy_term'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Extend options.
   *
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['reference_field'] = ['default' => FALSE];
    return $options;
  }


  /**
   * @inheritdoc
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
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
      '#description' => $this->t('The Term Reference field name (machine name) on the @type type.', [
        '@type' => $this->entityTypeLabel,
      ]),
      '#options' => $fields,
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * @inheritdoc
   */
  public function query($group_by = FALSE) {
    // Get the DB table and reference column name from the reference field name.
    $ref_field_name = $this->options['reference_field'] . '_target_id';
    $ref_field_table = $this->entityType . '__' . $this->options['reference_field'];

    ksm($this->options);

    $this->ensureMyTable();

    if (!empty($this->options['break_phrase'])) {
      $break = static::breakString($this->argument);
      if ($break->value === [-1]) {
        return FALSE;
      }

      $operator = (count($break->value) > 1) ? 'IN' : '=';
      $tids = $break->value;
    }
    else {
      $operator = "=";
      $tids = $this->argument;
    }

    // Now build the subqueries.
    $subquery = \Drupal::database()->select($ref_field_table, 'es');
    $subquery->addField('es', 'entity_id');
    $where = new Condition('OR');
    $where->condition('es.' . $ref_field_name, $tids, $operator);
    $last = "es";

    if ($this->options['depth'] > 0) {
      $subquery->leftJoin('taxonomy_term__parent', 'tp', "tp.entity_id = es." . $ref_field_name);
      $last = "tp";
      foreach (range(1, abs($this->options['depth'])) as $count) {
        $subquery->leftJoin('taxonomy_term__parent', "tp$count", "$last.parent_target_id = tp$count.entity_id");
        $where->condition("tp$count.entity_id", $tids, $operator);
        $last = "tp$count";
      }
    }
    elseif ($this->options['depth'] < 0) {
      foreach (range(1, abs($this->options['depth'])) as $count) {
        $subquery->leftJoin('taxonomy_term__parent', "tp$count", "$last.entity_id = tp$count.parent_target_id");
        $where->condition("tp$count.entity_id", $tids, $operator);
        $last = "tp$count";
      }
    }

    $subquery->condition($where);
    $this->query->addWhere(0, "$this->tableAlias.$this->realField", $subquery, 'IN');
  }

  /**
   * @inheritdoc
   */
  public function title() {
    $term = $this->termStorage->load($this->argument);
    if (!empty($term)) {
      $title = $term->getName();
      return $title;
    }
    return $this->t('No name');
  }
}
