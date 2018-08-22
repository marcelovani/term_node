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
   * Figures out if a different path should be used.
   *
   * @var TermResolverInterface
   */
  protected $termResolver;

  /**
   * Figures out if a different path should be used.
   *
   * @var NodeResolverInterface
   */
  protected $nodeResolver;

  /**
   * The path to use for the term.
   *
   * @var string
   */
  protected $path;

  /**
   * Whether the path has been processed.
   *
   * @var bool
   */
  protected $pathProcessed = FALSE;

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
    TermResolverInterface $term_resolver,
    NodeResolverInterface $node_resolver
  ) {
    $this->aliasManager = $alias_manager;
    $this->moduleHandler = $module_handler;
    $this->termResolver = $term_resolver;
    $this->nodeResolver = $node_resolver;
  }

  /**
   * Change the path if needed.
   *
   * @param string $path
   *  The internal path.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *  The page request object.
   */
  public function processPath($path, Request $request) {
    if ($this->pathProcessed) {
      return;
    }
    $this->pathProcessed = TRUE;

    $redirect_module = $this->moduleHandler->moduleExists('redirect');

    $parts = explode('/', trim($path, '/'));
    $count = count($parts);

    if ($count == 2 && $parts[0] == 'node') {
      // If the node is a term_node, do not redirect to the term path
      // when using the node's own path.
      if ($redirect_module && $this->nodeResolver->getReferencedBy($parts[1])) {
        // Don't redirect.
        $request->attributes->add(['_disable_route_normalizer' => TRUE]);
      }
    }
    elseif ($count == 3 && $parts[1] == 'term') {
      // If the term has node referenced, show the node content
      // but do not redirect to the node itself.
      $new_path = $this->termResolver->getPath($request, $path, $parts[2]);
      if ($new_path != $path) {
        $this->path = $new_path;
        // Don't redirect due to the path changing.
        $request->attributes->add(['_disable_route_normalizer' => TRUE]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $this->processPath($path, $request);

    if (!empty($this->path)) {
      return $this->path;
    }

    return $path;
  }

  /**
   * Set the path ready for processInbound() and disable redirecting if the path changes.
   *
   * Only used is the redirect module is enabled.
   * Has to be done in the kernel request event as the RouteNormalizerRequestSubscriber
   * performs the redirect on the kernel request event. This therefore has to
   * run before RouteNormalizerRequestSubscriber::onKernelRequestRedirect()
   * to disable the redirect, if needed, before it happens.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function onKernelRequest(GetResponseEvent $event) {
    // Only run this if the redirect module is enabled.
    if ($this->moduleHandler->moduleExists('redirect')) {
      $request = $event->getRequest();

      // Get the internal path.
      $alias = $request->getPathInfo();
      $alias = $alias === '/' ? $alias : rtrim($request->getPathInfo(), '/');
      $path = $this->aliasManager->getPathByAlias($alias);

      // See if the path needs changing.
      $this->processPath($path, $request);
    }
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
