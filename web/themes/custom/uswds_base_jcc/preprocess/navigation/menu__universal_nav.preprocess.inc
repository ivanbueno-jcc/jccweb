<?php

/**
 * @file
 * Preprocess function for this hook.
 */

/**
 * Implements hook_preprocess_menu__REGION().
 *
 * This made possible by our custom theme suggestion.
 *
 * @see uswds_theme_suggestions_menu_alter()
 */
function uswds_base_jcc_preprocess_menu__universal_nav(&$variables) {

  // set menu to active if current hostname
  $items = $variables['items'];
  foreach ($items as $key => $item) {
      // Add active-trail class
      if (parse_url($item['url']->toString(), PHP_URL_HOST) == \Drupal::request()->getHost()) {
        $variables['items'][$key]['attributes']->addClass('active');
      }
  }

}
