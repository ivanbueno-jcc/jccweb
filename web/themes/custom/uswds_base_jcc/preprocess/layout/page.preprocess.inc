<?php

/**
 * @file
 * Preprocess function for this hook.
 */

/**
 * Implements hook_preprocess_page().
 */
function uswds_base_jcc_preprocess_page(&$variables) {

  $variables['main_classes'] = 'grid-container';
  // Check to see if we need to turn on edge-to-edge mode.
  if (_uswds_edge_to_edge_mode()) {
    // If edge-to-edge mode is on, then the content area will not be wrapped in
    // "usa-grid", and the sidebars will be disabled.
    $variables['edge_to_edge'] = TRUE;
    $variables['main_classes'] = '';
    $variables['content_class'] = 'grid-col-12';
    $variables['page']['sidebar_first'] = [];
    $variables['page']['sidebar_second'] = [];
  }

}
