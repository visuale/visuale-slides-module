<?php

namespace Drupal\visuale_front_slides\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\node\Entity\Node;
use \Drupal\Component\Utility;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

/**
 * Block for insertion in designated region in Visuale site on front page
 * 
 * @Block(
 * id="visuale_front_slides_block",
 * admin_label=@Translation("Visuale Front Slides"),
 * )
 */

class VisualeFrontSlidesBlock extends BlockBase {
    /**
     * {@inheritdoc}
     */
    public function build() {

        $query = \Drupal::entityQuery('node')
                    ->condition('type', 'development_projects')
                    ->condition('status',1);
        $entity_ids = $query->execute();

        
        $nodes = \Drupal::entityTypeManager()
                ->getStorage('node')
                ->loadMultiple($entity_ids);
        
        $build = [];

        $slide_style = \Drupal::entityTypeManager()->getStorage('image_style')->load('slideshow_thumbnail');
        
        foreach($nodes as $node) {

            $img_val = $node->get('field_main_project_image')->getValue();
            $img_send = '';
            $styled_image_url = '';
            
            $slideshow_img_send = '';
            if($img_val) {
                $img_file_id = $img_val[0]['target_id'];
                $img_uri = \Drupal\file\Entity\File::load($img_file_id)->getFileUri();
                $img_send = file_create_url($img_uri);
                $styled_image_url = ImageStyle::load('slideshow_thumbnail')->buildUrl($node->field_main_project_image->entity->getFileUri());

            }

            // Get URL Alias
            $alias      = '';
            $path = '/node/' . (int) $node->id();
            $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
            $path_alias = \Drupal::service('path.alias_manager')->getAliasByPath($path, $langcode);

            $build[] = [
                'id'=>$node->id(),
                'title'=>$node->getTitle(),
                'location'=>$node->get('field_client_location')->getValue(),
                'image'=>$img_send,
                'slideshow_thumbnail' => $styled_image_url,
                'rel_url'=>$node->toUrl()->toString(),
                'modal_notes'=>$node->get('field_client_notes')->getValue(),
                'url_alias'=>$path_alias,
            ];
           
        }

        return [
            '#theme'=>'visuale_front_slides',
            '#frontslidesbatch'=>$build,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function blockAccess(\Drupal\Core\Session\AccountInterface $account)
    {
        return AccessResult::allowedIfHasPermission($account,'access content');
    }

    /**
     * {@inheritdoc}
     */
    public function blockForm($form,\Drupal\Core\Form\FormStateInterface $form_state) {
        $config = $this->getConfiguration();
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function blockSubmit($form, \Drupal\Core\Form\FormStateInterface $form_state) {
        $this->configuration['visuale_front_slide_settings'] = $form_state->getValue('visuale_front_slide_settings');
    }
}
