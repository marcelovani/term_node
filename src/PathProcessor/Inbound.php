<?php

namespace Drupal\term_node\PathProcessor;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\term_node\TermResolverInterface;
use Drupal\term_node\NodeResolverInterface;
use Drupal\term_node\ResolverInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;


/**
 * Processes the inbound path using path alias lookups.
 */
class Inbound implements InboundPathProcessorInterface, EventSubscriberInterface {

  /**
   * The core alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The core module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Handles changing the path if needed.
   *
   * @var \Drupal\term_node\PathProcessor\InboundPathInterface
   */
  protected $inboundPath;

  /**
   * Constructs a Inbound object.
   *
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *  An alias manager for looking up the system path.
   * @param ResolverInterface $resolver
   *  Resolves which path to use.
   */
  public function __construct(
    AliasManagerInterface $alias_manager,
    ModuleHandlerInterface $module_handler,
    InboundPathInterface $inbound_path
  ) {
    $this->aliasManager = $alias_manager;
    $this->moduleHandler = $module_handler;
    $this->inboundPath = $inbound_path;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    return $this->inboundPath->process($path, $request);
  }

  /**
   * Set the path ready for processInbound() and disable redirecting if the path changes.
   *
   * Has to be done in the kernel request event as the RouteNormalizerRequestSubscriber
   * performs the redirect on the kernel request event. This therefore has to
   * run before RouteNormalizerRequestSubscriber::onKernelRequestRedirect()
   * to disable the redirect, if needed, before it happens.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function onKernelRequest(GetResponseEvent $event) {
    $request = $event->getRequest();

    // Get the internal path.
    $alias = $request->getPathInfo();
    $alias = $alias === '/' ? $alias : rtrim($request->getPathInfo(), '/');
    $path = $this->aliasManager->getPathByAlias($alias);

    // See if the path needs changing.
    $this->inboundPath->process($path, $request);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Must happen before
    // Drupal\redirect\EventSubscriber\RouteNormalizerRequestSubscriber::onKernelRequestRedirect().
    $events[KernelEvents::REQUEST][] = ['onKernelRequest', 50];
    return $events;
  }

}
