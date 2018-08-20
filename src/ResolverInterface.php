<?php

namespace Drupal\term_node;

use Symfony\Component\HttpFoundation\Request;

interface ResolverInterface {

  /**
   * The path that should be used for this request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param string $path
   * @param int $entity_id
   *
   * @return string
   */
  public function getPath(Request $request, $path, $entity_id);

}
