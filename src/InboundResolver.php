<?php

namespace Drupal\term_node;

use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\Request;

class InboundResolver implements ResolverInterface, InboundResolverInterface {

  /**
   * @inheritDoc
   */
  public function getPath(Request $request, $path, $tid) {
    // Get the node id from the field if it exists.
    if ($term = Term::load($tid)) {
      if ($id = $this->getReferencedId($term)) {
        return '/node/' . $id;
      }
    }

    return $path;
  }

  /**
   * @inheritDoc
   */
  public function getReferencedId(Term $term) {
    if ($term->hasField('field_term_node')) {
      return $term->get('field_term_node')->target_id;
    }

    return FALSE;
  }

}
