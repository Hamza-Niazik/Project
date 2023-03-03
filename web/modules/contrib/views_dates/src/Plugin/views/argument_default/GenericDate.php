<?php

namespace Drupal\views_dates\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default argument plugin to extract a node.
 *
 * @ViewsArgumentDefault(
 *   id = "views_dates_generic_date",
 *   title = @Translation("Generic date as query parameter")
 * )
 */
class GenericDate extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['generic_date_query_param'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['generic_date_query_param'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Query parameter for Generic Date'),
      '#description' => $this->t('The query parameter to use.'),
      '#default_value' => $this->options['generic_date_query_param'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    $current_request = $this->view->getRequest();

    if ($current_request->query->has($this->options['generic_date_query_param'])) {
      return $current_request->query->get($this->options['generic_date_query_param']);
    }
    else {
      // Otherwise, return the current month as s fallback value.
      return 'CCYYMM_' . date('Ym');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url'];
  }

}
