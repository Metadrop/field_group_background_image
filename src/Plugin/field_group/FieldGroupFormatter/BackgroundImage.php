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

    if ($id = $this->getSetting('id')) {
      $attributes['id'] = Html::getId($id);
    }

    if ($classes = $this->getSetting('classes')) {
      $attributes['class'] = explode(' ', $classes);
    }
    $attributes['class'][] = 'field-group-background-image';

    // Image
    $fid = $rendering_object['#' . $this->group->entity_type]->get('field_image')->getValue()[0]['target_id'];
    $file = File::load($fid);
    $url = ImageStyle::load($this->getSetting('image_style'))->buildUrl($file->getFileUri());
    $attributes['style'] = strtr("background-image: url('@url')", ['@url' => $url]);

    $element['#type'] = 'container';
    $element['#attributes'] = $attributes;

  }

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

    $imageFields = $this->getImageFields();
// @todo add check for empty imagefields.
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($image = $this->getSetting('image')) {
      $summary[] = $this->t('Image: @image', ['@image' => $image]);
    }

    if ($imageStyle = $this->getSetting('image_style')) {
      $summary[] = $this->t('Image style: @style', ['@style' => $imageStyle]);
    }

    return $summary;
  }

}
