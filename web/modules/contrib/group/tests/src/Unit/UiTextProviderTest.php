<?php

namespace Drupal\Tests\group\Unit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationType;
use Drupal\group\Plugin\Group\RelationHandlerDefault\UiTextProvider;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the default group relation ui_text_provider handler.
 *
 * @coversDefaultClass \Drupal\group\Plugin\Group\RelationHandlerDefault\UiTextProvider
 * @group group
 */
class UiTextProviderTest extends UnitTestCase {

  use StringTranslationTrait;

  /**
   * The UI text provider to run tests on,
   *
   * @var \Drupal\group\Plugin\Group\RelationHandlerDefault\UiTextProvider
   */
  protected $uiTextProvider;

  /**
   * The entity type to run tests on.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityType;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeManager;

  /**
   * The group relation type to run tests on.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface
   */
  protected $groupRelationType;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->entityType = $this->prophesize(EntityTypeInterface::class);
    $this->entityType->getSingularLabel()->willReturn(new TranslatableMarkup('Some singular label'));

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityTypeManager->getDefinition('foo')->willReturn($this->entityType->reveal());

    $this->groupRelationType = new GroupRelationType([
      'id' => 'some_plugin',
      'entity_type_id' => 'foo',
    ]);

    $this->stringTranslation = $this->prophesize(TranslationInterface::class)->reveal();
    $this->uiTextProvider = new UiTextProvider($this->entityTypeManager->reveal(), $this->stringTranslation);
    $this->uiTextProvider->init('some_plugin', $this->groupRelationType);
  }

  /**
   * Tests the relationship label getter.
   *
   * @covers ::getRelationshipLabel
   */
  public function testGetRelationLabel() {
    $label = new TranslatableMarkup('Foo bar');

    $target_entity = $this->prophesize(EntityInterface::class);
    $target_entity->label()->willReturn($label);
    $group_relationship = $this->prophesize(GroupRelationshipInterface::class);
    $group_relationship->getEntity()->willReturn($target_entity->reveal());

    $this->assertEquals($label, $this->uiTextProvider->getRelationshipLabel($group_relationship->reveal()), 'The relationship label matches the grouped entity label.');
  }

  /**
   * Tests the add page label getter.
   *
   * @covers ::getAddPageLabel
   */
  public function testGetAddPageLabel() {
    $label = new TranslatableMarkup('Foo bar');
    $this->groupRelationType->set('label', $label);
    foreach ([TRUE, FALSE] as $create_mode) {
      $this->assertEquals($label, $this->uiTextProvider->getAddPageLabel($create_mode), 'The relation type label is used in both modes.');
    }
  }

  /**
   * Tests the add page description getter.
   *
   * @param string $description
   *   The expected description, with placeholders still.
   * @param bool$create_mode
   *   Whether the description is for an add or create page.
   * @param bool $bundle
   *   (optional) Whether the relation type supports bundles. Defaults to FALSE.
   *
   * @covers ::getAddPageDescription
   * @dataProvider getAddPageDescriptionProvider
   */
  public function testGetAddPageDescription($description, $create_mode, $bundle = FALSE) {
    $params['%entity_type'] = new TranslatableMarkup('Some singular label');

    if ($bundle) {
      $bundle_label = new TranslatableMarkup('Some bundle');

      $this->entityType->getBundleEntityType()->willReturn('some_bundle_entity_type');
      $this->groupRelationType->set('entity_bundle', 'some_bundle_id');

      $bundle_entity = $this->prophesize(EntityInterface::class);
      $bundle_entity->label()->willReturn($bundle_label);
      $params['%bundle'] = $bundle_label;

      $storage = $this->prophesize(EntityStorageInterface::class);
      $storage->load('some_bundle_id')->willReturn($bundle_entity->reveal());

      $this->entityTypeManager->getStorage('some_bundle_entity_type')->willReturn($storage->reveal());
    }

    $this->assertEquals($this->t($description, $params), $this->uiTextProvider->getAddPageDescription($create_mode));
  }

  /**
   * Data provider for testGetAddPageDescription().
   *
   * @return array
   *   A list of testGetAddPageDescription method arguments.
   */
  public function getAddPageDescriptionProvider() {
    $cases['add-no-bundle'] = [
      'Add existing %entity_type to the group.',
      FALSE,
    ];

    $cases['create-no-bundle'] = [
      'Add new %entity_type to the group.',
      TRUE,
    ];

    $cases['add-with-bundle'] = [
      'Add existing %entity_type of type %bundle to the group.',
      FALSE,
      TRUE,
    ];

    $cases['create-with-bundle'] = [
      'Add new %entity_type of type %bundle to the group.',
      TRUE,
      TRUE,
    ];

    return $cases;
  }

  /**
   * Tests the add form title getter.
   *
   * @covers ::getAddFormTitle
   */
  public function testGetAddFormTitle() {
    $label = new TranslatableMarkup('Foo bar');
    $this->groupRelationType->set('label', $label);

    $title = $this->t('Add @name', ['@name' => $label]);
    foreach ([TRUE, FALSE] as $create_mode) {
      $this->assertEquals($title, $this->uiTextProvider->getAddFormTitle($create_mode), 'The relation type label is used in both modes.');
    }
  }

}
