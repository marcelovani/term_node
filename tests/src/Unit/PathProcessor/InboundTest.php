<?php

namespace Drupal\Tests\term_node\Unit\PathProcessor;

use Drupal\term_node\PathProcessor\Inbound;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;


/**
 * Tests inbound alter
 *
 * @group term_node
 *
 * @coversDefaultClass \Drupal\term_node\PathProcessor\Inbound
 */
class InboundTest extends UnitTestCase {

  /**
   * @covers ::processInbound
   */
  public function testProcessInbound() {
    // Mock the alias manager.
    $alias_manager = $this->getMockBuilder('\Drupal\Core\Path\AliasManagerInterface')
      ->getMock();

    // Mock the module manager
    $module_handler = $this->getMockBuilder('\Drupal\Core\Extension\ModuleHandlerInterface')
      ->getMock();

    // Mock the InboundPathInterface
    $inbound_path = $this->getMockBuilder('\Drupal\term_node\PathProcessor\InboundPathInterface')
      ->getMock();
    $inbound_path->method('process')
      ->willReturn('/foo');

    $inbound = new Inbound($alias_manager, $module_handler, $inbound_path);
    $path = $inbound->processInbound('/foo', Request::create('/'));

    // Test that the path argument is returned.
    $this->assertEquals('/foo', $path);
  }

  /**
   * @covers ::getSubscribedEvents
   */
  public function testGetSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onKernelRequest', 50];
    // Ensure the event listener is configured.
    $this->assertEquals($events, Inbound::getSubscribedEvents());
  }

}
