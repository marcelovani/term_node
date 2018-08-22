<?php

namespace Drupal\term_node\PathProcessor;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface InboundPathInterface
 *
 * @package Drupal\term_node\PathProcessor
 */
interface InboundPathInterface {

  /**
   * Change the path if needed.
   *
   * @param string $path
   *  The internal path.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *  The page request object.
   *
   * @return string
   *  The path to use.
   */
  public function process($path, Request $request);

}
