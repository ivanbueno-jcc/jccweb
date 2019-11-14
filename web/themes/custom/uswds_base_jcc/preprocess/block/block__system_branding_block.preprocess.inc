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
function uswds_base_jcc_preprocess_block__system_branding_block(&$variables) {
  // Show/hide site name.
  $variables['header_hide_site_name'] = theme_get_setting('uswds_header_hide_site_name');
}
