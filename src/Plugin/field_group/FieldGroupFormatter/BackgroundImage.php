<?php

/**
 * @file
 * Contains \Drupal\field_group_background_image\Plugin\field_group\FieldGroupFormatter\Link.
 */

namespace Drupal\field_group_background_image\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Template\Attribute;
use Drupal\field_group\FieldGroupFormatterBase;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

/**
 * Plugin implementation of the 'background image' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "background_image",
 *   label = @Translation("Background Image"),
 *   description = @Translation("Field group as a background image."),
 *   supported_contexts = {
 *     "view",
 *   }
 * )
 */
class BackgroundImage extends FieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {
    $attributes = new Attribute();   

    // Add the HTML ID.
    if ($id = $this->getSetting('id')) {
      $attributes['id'] = Html::getId($id);
    }

    // Add the HTML classes.
    if ($classes = $this->getSetting('classes')) {
      $attributes['class'] = explode(' ', $classes);
    }
    $attributes['class'][] = 'field-group-background-image';

    // @todo: check image style!s

    // Add the background image when a field has been selected in the settings form
    // and when it is still present at the time of rendering.
    if (($image = $this->getSetting('image')) && array_key_exists($image, $this->getImageFields())) {
      // Only add a background image if one is present.
      if ($imageFieldValue = $rendering_object['#' . $this->group->entity_type]->get($image)->getValue()) {
        $fid = $imageFieldValue[0]['target_id'];
        $fileUri = File::load($fid)->getFileUri();
        $url = ImageStyle::load($this->getSetting('image_style'))->buildUrl($fileUri);
        $attributes['style'] = strtr("background-image: url('@url')", ['@url' => $url]);
      }    
    }

    // Render the element as a HTML div and add the attributes.
    $element['#type'] = 'container';
    $element['#attributes'] = $attributes;
  }

  /**
   * Get all image fields for the current entity and bundle.
   * @return array
   */
  protected function getImageFields() {
    $fields = \Drupal::entityManager()->getFieldDefinitions($this->group->entity_type, $this->group->bundle);

    $imageFields = [];
    foreach ($fields as $field) {
      if ($field->getType() === 'image') {
        $imageFields[$field->get('field_name')] = $field->label();
      }
    }

    return $imageFields;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $form = parent::settingsForm();

    if ($imageFields = $this->getImageFields()) {
      $form['image'] = [
        '#title' => t('Image'),
        '#type' => 'select',
        '#options' => $imageFields,
        '#default_value' => $this->getSetting('image'),
        '#weight' => 1,
      ];

      $form['image_style'] = [
        '#title' => t('Image style'),
        '#type' => 'select',
        '#options' => image_style_options(FALSE),
        '#default_value' => $this->getSetting('image_style'),
        '#weight' => 2,
      ];
    } else {
      $form['error'] = [
        '#markup' => t('Please add an image field to continue.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($image = $this->getSetting('image')) {
      $imageFields = $this->getImageFields();
      $summary[] = $this->t('Image field: @image', ['@image' => $imageFields[$image]]);
    }

    if ($imageStyle = $this->getSetting('image_style')) {
      $summary[] = $this->t('Image style: @style', ['@style' => $imageStyle]);
    }

    return $summary;
  }

}
