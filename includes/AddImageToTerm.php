<?php

namespace SafeTermMediaDelete;

class AddImageToTerm
{
     public function __construct()
     {
         add_action( 'cmb2_admin_init', [$this,"add_taxonomy_image_field"] );
     }
    function add_taxonomy_image_field() {
        $cmb = new_cmb2_box( array(
            "id"    => "term_image_upload",
            'title' => __( 'Taxonomy Image', 'cmb2' ),
            'object_types' => array( 'term' ),
            'taxonomies'   => array( 'category', 'post_tag' ),
        ));

        $cmb->add_field( array(
            'name'    => __( 'Image', 'cmb2' ),
            'desc'    => __( 'Upload or select an image for this term.', 'cmb2' ),
            'id'      => 'term_image_upload',
            'type'    => 'file',
            'options' => array(
                'url' => false, // Return the URL of the image, not the ID.
            ),
            'text'    => array(
                'add_upload_file_text' => __( 'Add Image', 'cmb2' ),
            ),
            'query_args' => array(
                'type' => array(
                    'image/jpeg',
                    'image/png',
                ),
            ),
        ) );
    }

}