<?php

namespace Drupal\jcc_misc\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\media\MediaInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'media_url' formatter.
 *
 * @FieldFormatter(
 *   id = "media_url",
 *   label = @Translation("Media URL"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class MediaUrl extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $media_items = $this->getEntitiesToView($items, $langcode);

    if (empty($media_items)) {
      return $elements;
    }

    /** @var \Drupal\media\MediaInterface[] $media_items */
    foreach ($media_items as $delta => $media) {
      // Only handle media objects.
      if ($media instanceof MediaInterface) {

        $file = File::load($media->getSource()->getSourceFieldValue($media));
        $url = Url::fromUri(file_create_url($file->getFileUri()))->toString();

        $elements[$delta] = [
          '#type' => 'inline_template',
          '#template' => '<a href="{{ url }}" target="_blank">Download {{ filename }}</a>',
          '#context' => [
            'url' => $url,
            'filename' => $file->getFilename(),
          ],
        ];

      }
    }

    return $elements;
  }

}
