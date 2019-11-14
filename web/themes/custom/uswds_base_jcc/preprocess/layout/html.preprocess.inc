<?php

/**
 * @file
 * Preprocess function for this hook.
 */

/**
 * Implements hook_preprocess_html().
 */
function uswds_base_jcc_preprocess_html(&$variables) {

  if (isset($variables['node_type'])) {
    // Add node-TYPE class to the <body> element.
    $variables['attributes']['class'][] = 'page-node-type--' . $variables['node_type'];
  }

}
