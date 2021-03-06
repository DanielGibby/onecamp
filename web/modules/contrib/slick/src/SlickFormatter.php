<?php

namespace Drupal\slick;

use Drupal\Component\Utility\UrlHelper;
use Drupal\slick\Entity\Slick;
use Drupal\blazy\BlazyFormatter;
use Drupal\image\Plugin\Field\FieldType\ImageItem;

/**
 * Provides Slick field formatters utilities.
 */
class SlickFormatter extends BlazyFormatter implements SlickFormatterInterface {

  /**
   * {@inheritdoc}
   */
  public function buildSettings(array &$build, $items) {
    $settings = &$build['settings'];

    // Prepare integration with Blazy.
    $settings['item_id']   = 'slide';
    $settings['namespace'] = 'slick';
    $settings['_unload']   = FALSE;

    // @todo move this into ::prepareData() post Blazy 2.7.
    $build['optionset'] = $optionset = Slick::loadWithFallback($settings['optionset']);
    if (isset($settings['blazies'])) {
      $blazies = &$settings['blazies'];
      $blazies->set('initial', $optionset->getSetting('initialSlide'));
    }

    // Pass basic info to parent::buildSettings().
    parent::buildSettings($build, $items);
  }

  /**
   * {@inheritdoc}
   */
  public function preBuildElements(array &$build, $items, array $entities = []) {
    parent::preBuildElements($build, $items, $entities);

    $settings = &$build['settings'];

    // Only display thumbnail nav if having at least 2 slides. This might be
    // an issue such as for ElevateZoom Plus module, but it should work it out.
    if (!isset($settings['nav'])) {
      $settings['nav'] = !empty($settings['optionset_thumbnail']) && isset($items[1]);
    }

    // Do not bother for SlickTextFormatter, or when vanilla is on.
    if (empty($settings['vanilla'])) {
      $build['optionset']->whichLazy($settings);
    }
    else {
      // Nothing to work with Vanilla on, disable the asnavfor, else JS error.
      $settings['nav'] = FALSE;
    }

    // Only trim overridables options if disabled.
    if (empty($settings['override']) && isset($settings['overridables'])) {
      $settings['overridables'] = array_filter($settings['overridables']);
    }

    $this->getModuleHandler()->alter('slick_settings', $build, $items);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Remove post Blazy 2.5+.
   */
  public function getThumbnail(array $settings = [], $item = NULL) {
    if (!empty($settings['uri'])) {
      $external = UrlHelper::isExternal($settings['uri']);
      return [
        '#theme'      => $external ? 'image' : 'image_style',
        '#style_name' => empty($settings['thumbnail_style']) ? 'thumbnail' : $settings['thumbnail_style'],
        '#uri'        => $settings['uri'],
        '#item'       => $item,
        '#alt'        => $item && $item instanceof ImageItem ? $item->getValue()['alt'] : '',
      ];
    }
    return [];
  }

}
