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
  public function preRender(&$element, $renderingObject) {
    $attributes = new Attribute();   

    // Add the HTML ID.
    if ($id = $this->getSetting('id')) {
      $attributes['id'] = Html::getId($id);
    }

    // Add the HTML classes.
    $attributes['class'] = $this->getClasses();

    // Add the image as a background.
    $image = $this->getSetting('image');
    $imageStyle = $this->getSetting('image_style');
    if ($style = $this->generateStyleAttribute($renderingObject, $image, $imageStyle)) {
      $attributes['style'] = $style;
    }
    elseif ($this->getSetting('hide_if_missing')) {
      hide($element);
    }
    
    // Render the element as a HTML div and add the attributes.
    $element['#type'] = 'container';
    $element['#attributes'] = $attributes;
  }

  /**
   * Generates the background image style attribute given an image and image 
   * style.
   *
   * @param string $image
   * @param string $imageStyle
   * @return string
   */
  protected function generateStyleAttribute($renderingObject, $image, $imageStyle) {
    $style = '';

    $validImage = array_key_exists($image, $this->imageFields());
    $validImageStyle = ($imageStyle === '') || array_key_exists($imageStyle, image_style_options(FALSE));

    if ($validImage && $validImageStyle) {
      if ($url = $this->imageUrl($renderingObject, $image, $imageStyle)) {
        $style = strtr('background-image: url(\'@url\')', ['@url' => $url]);
      }
    }

    return $style;
  }

  /**
   * Gets all HTML classes, cleaned for displaying.
   * 
   * @see \Drupal\field_group\FieldGroupFormatterBase::getClasses().
   * 
   * @return array
   */
  protected function getClasses() {
    $classes = parent::getClasses();
    $classes[] = 'field-group-background-image';
    $classes = array_map(['\Drupal\Component\Utility\Html', 'getClass'], $classes);

    return $classes;
  }

  /**
   * Returns an image URL for the rendered object given an image field name and 
   * an image style.
   *
   * @param $renderingObject
   * @param $image
   * @param $imageStyle
   * @return string
   */
  protected function imageUrl($renderingObject, $image, $imageStyle) {
    $url = '';

    if ($imageFieldValue = $renderingObject['#' . $this->group->entity_type]->get($image)->getValue()) {

      $fid = $imageFieldValue[0]['target_id'];
      $fileUri = File::load($fid)->getFileUri();

      // When no image style is selected, use the original image.
      if ($imageStyle === '') {
        $url = file_create_url($fileUri);
      }
      else {
        $url = ImageStyle::load($imageStyle)->buildUrl($fileUri);
      }
    }

    return $url;
  }

  /**
   * Get all image fields for the current entity and bundle.
   *
   * @return array Image field key value pair.
   */
  protected function imageFields() {
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

    $form['label']['#access'] = FALSE;

    if ($imageFields = $this->imageFields()) {
      $form['image'] = [
        '#title' => $this->t('Image'),
        '#type' => 'select',
        '#options' => [
          '' => $this->t('- Select -'),
        ],
        '#default_value' => $this->getSetting('image'),
        '#weight' => 1,
      ];
      $form['image']['#options'] += $imageFields;

      $form['image_style'] = [
        '#title' => $this->t('Image style'),
        '#type' => 'select',
        '#options' => [
          '' => $this->t('- Select -'),
        ],
        '#default_value' => $this->getSetting('image_style'),
        '#weight' => 2,
      ];
      $form['image_style']['#options'] += image_style_options(FALSE);

      $form['hide_if_missing'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Hide if missing image'),
        '#description' => $this->t('Do not render the field group if the image is missing from the selected field.'),
        '#default_value' => $this->getSetting('hide_if_missing'),
        '#weight' => 3,
      );
    }
    else {
      $form['error'] = [
        '#markup' => $this->t('Please add an image field to continue.'),
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
      $imageFields = $this->imageFields();
      $summary[] = $this->t('Image field: @image', ['@image' => $imageFields[$image]]);
    }

    if ($imageStyle = $this->getSetting('image_style')) {
      $summary[] = $this->t('Image style: @style', ['@style' => $imageStyle]);
    }

    return $summary;
  }

}
