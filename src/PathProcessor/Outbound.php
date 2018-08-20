<?php

namespace Drupal\term_node\PathProcessor;

use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\term_node\ResolverInterface;
use Symfony\Component\HttpFoundation\Request;


/**
 * Processes the outbound path of the referenced entity.
 */
class Outbound implements OutboundPathProcessorInterface {

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
   * Constructs a Outbound object.
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
   * @inheritDoc
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    $langcode = isset($options['language']) ? $options['language']->getId() : NULL;
    $original_path = $this->aliasManager->getPathByAlias($path, $langcode);

    // Only interested in node pages.
    if (strpos($original_path, '/node/') === 0) {
      // Now match on just the view path.
      if (preg_match('|/node/(\d+)$|', $original_path, $matches)) {
        $new_path = $this->resolver->getPath($request, $original_path, $matches[1]);
        if ($new_path != $original_path) {
          $path = $new_path;
        }
      }
    }

    return $path;
  }

}