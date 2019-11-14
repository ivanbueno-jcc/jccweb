<?php

/**
 * @file
 * Utility code related to page.
 */

/**
 * Implements hook_theme_suggestions_page_alter().
 *
 * @see https://www.drupal.org/node/2521876#comment-10684366
 */
function uswds_base_jcc_theme_suggestions_page_alter(array &$suggestions, array $variables) {
  $node     = \Drupal::routeMatch()->getParameter('node');
  $revision = \Drupal::routeMatch()->getRawParameter('node_revision');

  /*
  Revision IDs are strings and node ids are
  strings when a revision is loaded.
  Node parameters loaded by getParameter are objects
  and the bundle can be accessed normally.
   */
  if ($node) {
    if ($revision) {
      $revised_node = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadRevision($revision);

      $content_type = $revised_node->bundle();
    }
    else {
      $content_type = $node->bundle();
    }

    $suggestions[] = 'page__node__' . $content_type;
  }
}
