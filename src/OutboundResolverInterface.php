<?php

namespace Drupal\term_node;

interface OutboundResolverInterface {

  /**
   * The tid of the term referencing the content.
   *
   * @param int $entity_id
   *  The id of the entity being referenced.
   *
   * @return int
   */
  public function getReferencedBy($entity_id);

}
