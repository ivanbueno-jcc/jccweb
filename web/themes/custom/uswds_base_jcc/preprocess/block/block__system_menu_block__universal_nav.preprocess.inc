<?php

/**
 * @file
 * Preprocess function for this hook.
 */

/**
 * Implements hook_preprocess_block__DELTA__REGION().
 *
 * This made possible by our custom theme suggestion.
 *
 * @see uswds_theme_suggestions_block_alter()
 */
function uswds_base_jcc_preprocess_block__system_menu_block__universal_nav(&$variables) {
  
  // Hide Universal nav?
  if ($hide_universal_nav = theme_get_setting('uswds_header_hide_universal_nav')) {
    $variables['header_hide_universal_nav'] = $hide_universal_nav;
  }

}
