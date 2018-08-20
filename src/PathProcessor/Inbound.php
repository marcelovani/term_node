<?php

namespace Drupal\term_node\PathProcessor;

use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
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
   * An alias manager for looking up the system path.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Figures out if a different path should be used.
   *
   * @var ResolverInterface
   */
  protected $resolver;

  /**
   * The path to use for the term.
   *
   * @var string
   */
  protected $path;

  /**
   * Constructs a Inbound object.
   *
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *  An alias manager for looking up the system path.
   * @param ResolverInterface $resolver
   *  Resolves which path to use.
   */
  public function __construct(AliasManagerInterface $alias_manager, ResolverInterface $resolver) {
    $this->aliasManager = $alias_manager;
    $this->resolver = $resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if (!empty($this->path)) {
      return $this->path;
    }

    return $path;
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
    // Just trim on the right side.
    $path = $request->getPathInfo();
    $path = $path === '/' ? $path : rtrim($request->getPathInfo(), '/');
    $original_path = $this->aliasManager->getPathByAlias($path);

    // Only interested in default taxonomy term pages.
    if (strpos($original_path, '/taxonomy/term/') === 0) {
      // Now match on just the view path.
      if (preg_match('|/taxonomy/term/(\d+)$|', $original_path, $matches)) {
        $path = $this->resolver->getPath($request, $original_path, $matches[1]);
        if ($path != $original_path) {
          $this->path = $path;
          // Don't redirect due to the path changing.
          $request->attributes->add(['_disable_route_normalizer' => TRUE]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Must happen before RouteNormalizerRequestSubscriber::onKernelRequestRedirect().
    $events[KernelEvents::REQUEST][] = array('onKernelRequest', 50);
    return $events;
  }

}
