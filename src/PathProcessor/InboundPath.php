<?php

namespace Drupal\term_node\PathProcessor;

use Drupal\term_node\NodeResolverInterface;
use Drupal\term_node\TermResolverInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class InboundPath
 *
 * @package Drupal\term_node\PathProcessor
 */
class InboundPath implements InboundPathInterface {

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
   * The path to use.
   *
   * @var string
   */
  protected $path;

  /**
   * InboundPath constructor.
   *
   * @param \Drupal\term_node\TermResolverInterface $term_resolver
   *  Handles term path look ups.
   * @param \Drupal\term_node\NodeResolverInterface $node_resolver
   *  Handles node path look ups.
   */
  public function __construct(
    TermResolverInterface $term_resolver,
    NodeResolverInterface $node_resolver
  ) {
    $this->termResolver = $term_resolver;
    $this->nodeResolver = $node_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function process($path, Request $request) {
    if (!empty($this->path)) {
      return $this->path;
    }

    $parts = explode('/', trim($path, '/'));
    $count = count($parts);

    if ($count == 2 && $parts[0] == 'node') {
      // If the node is a term_node, do not redirect to the term path
      // when using the node's own path.
      if ($this->nodeResolver->getReferencedBy($parts[1])) {
        // Don't redirect.
        $request->attributes->add(['_disable_route_normalizer' => TRUE]);
      }
    }
    elseif ($count == 3 && $parts[1] == 'term') {
      // If the term has node referenced, show the node content
      // but do not redirect to the node itself.
      $new_path = $this->termResolver->getPath($path, $parts[2]);
      if ($new_path != $path) {
        $path = $this->path = $new_path;
        // Don't redirect due to the path changing.
        $request->attributes->add(['_disable_route_normalizer' => TRUE]);
      }
    }

    return $path;
  }

}
