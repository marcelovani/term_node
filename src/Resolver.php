<?php

namespace Drupal\term_node;

use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\Request;

class Resolver implements ResolverInterface {

  /**
   * @inheritDoc
   */
  public function getPath(Request $request, $path, $tid) {
    // Get the node id from the field if it exists.
    if ($term = Term::load($tid)) {
      if ($term->hasField('field_term_node')) {
        $vals = $term->get('field_term_node')->getValue();
        $val = reset($vals);
        if (isset($val['target_id'])) {
          return '/node/' . $val['target_id'];
        }
      }
    }

    return $path;
  }

}
