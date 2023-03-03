<?php

namespace Drupal\Tests\wysiwyg_template\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\wysiwyg_template\Entity\Template;

/**
 * Tests the template config entity.
 *
 * @group wysiwyg_template
 *
 * @coversDefaultClass \Drupal\wysiwyg_template\Entity\Template
 */
class TemplateTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['wysiwyg_template', 'node', 'user', 'system'];

  /**
   * Node types to test with.
   *
   * @var \Drupal\node\NodeTypeInterface[]
   */
  protected $nodeTypes;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('wysiwyg_template');
    $this->installEntitySchema('node_type');

    foreach (range(1, 3) as $i) {
      $this->nodeTypes[$i] = NodeType::create([
        'type' => strtolower($this->randomMachineName()),
        'name' => $this->randomString(),
      ]);
      $this->nodeTypes[$i]->save();
    }
  }

  /**
   * {@inheritdoc}
   *
   * @covers ::id
   * @covers ::label
   * @covers ::getBody
   * @covers ::getFormat
   * @covers ::getNodeTypes
   * @covers ::getWeight
   * @covers ::loadByNodeType
   */
  public function testInterfaceMethods() {
    $values = [
      'id' => strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'body' => [
        'value' => $this->randomString(),
        'format' => $this->randomMachineName(),
      ],
      'node_types' => [
        $this->nodeTypes[1]->id(),
        $this->nodeTypes[2]->id(),
      ],
      'weight' => -42,
    ];
    $template = Template::create($values);
    $template->save();

    /** @var \Drupal\wysiwyg_template_core\TemplateInterface $template */
    $template = Template::load($values['id']);
    self::assertEquals($values['id'], $template->id());
    self::assertEquals($values['label'], $template->label());
    self::assertEquals($values['body']['value'], $template->getBody());
    self::assertEquals($values['body']['format'], $template->getFormat());
    self::assertEquals($values['node_types'], $template->getNodeTypes());
    self::assertEquals($values['weight'], $template->getWeight());

    // Since this template specifies node types, it should not be returned if
    // no node types are specified.
    self::assertEquals([], Template::loadByNodeType());

    // It should return for types 1 and 2, but not 3.
    self::assertEquals([$template->id() => $template], Template::loadByNodeType($this->nodeTypes[1]));
    self::assertEquals([$template->id() => $template], Template::loadByNodeType($this->nodeTypes[2]));
    self::assertEquals([], Template::loadByNodeType($this->nodeTypes[3]));
  }

}
