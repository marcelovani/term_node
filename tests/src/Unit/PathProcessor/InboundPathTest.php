<?php

namespace Drupal\Tests\term_node\Unit\PathProcessor;

use Drupal\term_node\PathProcessor\Inbound;
use Drupal\term_node\PathProcessor\InboundPath;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;


/**
 * Tests inbound path processing.
 *
 * @group term_node
 *
 * @coversDefaultClass \Drupal\term_node\PathProcessor\Inbound
 */
class InboundPathTest extends UnitTestCase {

  /**
   * @covers ::process
   * @dataProvider getTestPaths
   */
  public function testProcess($in, $out, $no_redirect) {
    $request = Request::create('/');
    $term_resolver = $this->getMockBuilder('\Drupal\term_node\TermResolverInterface')
      ->getMock();
    $node_resolver = $this->getMockBuilder('\Drupal\term_node\NodeResolverInterface')
      ->getMock();
    $module_handler = $this->getMockBuilder('\Drupal\Core\Extension\ModuleHandlerInterface')
      ->getMock();

    $term_resolver->method('getPath')
      ->willReturn('/node/1');
    $node_resolver->method('getReferencedBy')
      ->willReturn(1);
    $module_handler->method('moduleExists')
      ->willReturn(TRUE);

    $inbound_path = new InboundPath($term_resolver, $node_resolver, $module_handler);

    $path = $inbound_path->process($in, $request);

    // Test that the path is returned, changed if needed.
    $this->assertEquals($out, $path);

    // Test redirect is off if changed.
    $redirect_disabled = $request->attributes->get('_disable_route_normalizer');
    $this->assertEquals($no_redirect, $redirect_disabled);
  }

  /**
   * Data provider for testProcessOutbound().
   */
  public function getTestPaths() {
    return [
      ['/taxonomy/term/2', '/node/1', TRUE],
      ['/taxonomy/term/2/edit', '/taxonomy/term/2/edit', NULL],
      ['/taxonomy/term/2/preview', '/taxonomy/term/2/preview', NULL],
      ['/entity/3', '/entity/3', NULL],
    ];
  }

}
