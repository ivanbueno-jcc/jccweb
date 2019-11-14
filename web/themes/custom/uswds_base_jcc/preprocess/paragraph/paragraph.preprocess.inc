<?php

/**
 * @file
 * Preprocess function for blocks.
 */

use Drupal\image\Entity\ImageStyle;

/**
 * Implements hook_preprocess_paragraph().
 */
function uswds_base_jcc_preprocess_paragraph(&$variables) {

  // Always set the USWDS-specific "uswds_grid_class" variable.
  if (!isset($variables['uswds_grid_class'])) {
    $variables['uswds_grid_class'] = FALSE;
  }

  // All of this is only necessary if edge-to-edge mode is on.
  if (!_uswds_edge_to_edge_mode()) {
    // But we should still set the grid class to usa-grid-full.
    $variables['uswds_grid_class'] = 'grid-col-fill';
    return;
  }

  // We want to make sure that Paragraphs are on the grid.
  // But there are some exceptions. We don't need to add the grid class if...
  // ...if this is a Paragraph on a Paragraph.
  $paragraph = $variables['paragraph'];
  if ('paragraph' == $paragraph->getParentEntity()->getEntityTypeId()) {
    return;
  }

  // ...if this is one of a few Paragraph types that shouldn't be on the grid.
  // These types are provided by the USWDS Paragraphs module.
  $exclude_types = [
    'uswds_hero_callout',
    'uswds_media_block',
  ];
  if (in_array($paragraph->bundle(), $exclude_types)) {
    return;
  }

  // If still here, add the grid class.
  $variables['uswds_grid_class'] = 'grid-container';

  // Get the url of the background image if set.
  if (isset($variables['content']['field_paragraph_background_image'][0]) && isset($variables['content']['field_paragraph_background_image'][0]['#media'])) {
    $image_style = ImageStyle::load('manual_crop');
    if ($image_style) {
      $variables['background_image_url'] = $image_style->buildUrl($variables['content']['field_paragraph_background_image'][0]['#media']->field_media_image->entity->getFileUri());
    }
  }
}
