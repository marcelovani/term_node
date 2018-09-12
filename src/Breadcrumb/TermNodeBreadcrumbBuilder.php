<?php

namespace Drupal\term_node\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to define the term node breadcrumb builder.
 */
class TermNodeBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * The router request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $context;

  /**
   * An alias manager for looking up the system path.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  public function __construct(RequestContext $context, AliasManagerInterface $alias_manager) {
    $this->context = $context;
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $path = '/'. trim($this->context->getPathInfo(), '/');
    $internal = $this->aliasManager->getPathByAlias($path);
    $parts = explode('/', trim($internal, '/'));
    $count = count($parts);
    if ($count == 3 && $parts[1] == 'term') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {

    $path = '/'. trim($this->context->getPathInfo(), '/');

    $breadcrumb = new Breadcrumb();
    $links = [];

    $breadcrumb->addCacheContexts(['url.path']);

    $links[] = new Link("Foo", Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => 1]));

    // Add the Home link.
    $links[] = Link::createFromRoute($this->t('Home'), '<front>');

    return $breadcrumb->setLinks(array_reverse($links));
  }

}
