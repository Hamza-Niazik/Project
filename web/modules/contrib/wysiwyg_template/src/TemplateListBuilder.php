<?php

namespace Drupal\wysiwyg_template;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a listing of Template entities.
 */
class TemplateListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'wysiwyg_template_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Template');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['label'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Better empty text.
    $form[$this->entitiesKey]['#empty'] = $this->t('There are no WYSIWYG templates yet.');

    return $form;
  }

}
