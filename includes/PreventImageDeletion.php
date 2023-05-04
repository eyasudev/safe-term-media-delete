<?php

namespace SafeTermMediaDelete;

class PreventImageDeletion
{
    public function __construct()
    {
        add_action( 'delete_attachment', [$this,"prevent_image_deletion"] );

        add_filter( 'manage_media_columns', [$this,"add_post_id_column_to_media_list"] );
        add_action( 'manage_media_custom_column', [$this,"show_post_id_column_content_in_media_list"], 10, 2 );

    }


    function prevent_image_deletion( $post_id ) {
        // check if the attachment is an image
        if ( wp_attachment_is_image( $post_id ) ) {
            // check if the image is being used as a featured image
            $posts = get_posts( array(
                'meta_query' => array(
                    array(
                        'key' => '_thumbnail_id',
                        'value' => $post_id,
                        'compare' => '='
                    )
                )
            ) );
            if ( count( $posts ) > 0 ) {
                wp_die( 'This image is being used as a featured image and cannot be deleted.' );
            }

            // check if the image is being used in the content of a post
            $args = array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'meta_query' => array(
                    array(
                        'key' => '_thumbnail_id',
                        'value' => $post_id,
                        'compare' => '!='
                    )
                ),
                'tax_query' => array(
                    array(
                        'taxonomy' => 'category',
                        'field' => 'id',
                        'terms' => array( 'uncategorized' ), // you can change this to the category ID of your choice
                        'operator' => 'NOT IN'
                    )
                )
            );
            $posts = get_posts( $args );
            $post_content = '';
            foreach ( $posts as $post ) {
                $post_content .= $post->post_content;
            }
            if ( strpos( $post_content, $post_id ) !== false ) {
                wp_die( 'This image is being used in the content of a post and cannot be deleted.' );
            }

            // check if the image is being used in a term edit page
            $terms = get_terms( array(
                'taxonomy' => array( 'category', 'post_tag', 'custom_taxonomy' ), // you can change this to the taxonomies you want to check
                'hide_empty' => false
            ) );
            foreach ( $terms as $term ) {
                $term_meta = get_term_meta( $term->term_id );
                foreach ( $term_meta as $key => $value ) {
                    if ( $value == $post_id ) {
                        wp_die( 'This image is being used in a term edit page and cannot be deleted.' );
                    }
                }
            }
        }
    }


    function add_post_id_column_to_media_list( $columns ) {
        $columns['post_id'] = __( 'Linked Objects', 'cmb2' );
        return $columns;
    }

    function show_post_id_column_content_in_media_list( $column_name, $post_id ) {
        if ( $column_name == 'post_id' ) {
            $post_ids = $this->get_attached_post_ids( $post_id );
            if ( $post_ids ) {
                echo implode( ', ', $post_ids );
            } else {
                echo '-';
            }
        }
    }

    function get_attached_post_ids( $attachment_id ) {
        $post_ids = array();
        $term_ids = array();
        $posts = get_posts( array(
            'post_type' => 'any',
            'meta_query' => array(
                "relation" => "OR",
                array(
                    'key' => '_thumbnail_id',
                    'value' => $attachment_id,
                    'compare' => '='
                ),
                array(
                    'key' => '_wp_attached_file',
                    'value' => basename( get_attached_file( $attachment_id ) ),
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_wp_attachment_metadata',
                    'value' => '"' . $attachment_id . '"',
                    'compare' => 'LIKE'
                )
            )
        ) );
        foreach ( $posts as $post ) {
            $post_ids[] = $post->ID;
        }

        // Find categories and tags
        $taxonomies = array( 'category', 'post_tag' );
        foreach ( $taxonomies as $taxonomy ) {
            $terms = get_terms( array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'meta_query' => array(
                    array(
                        'key' => '_thumbnail_id',
                        'value' => $attachment_id,
                        'compare' => '='
                    ),
                    array(
                        'key' => '_wp_attached_file',
                        'value' => basename( get_attached_file( $attachment_id ) ),
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => '_thumbnail_id',
                        'value' => '"' . $attachment_id . '"',
                        'compare' => 'LIKE'
                    )
                )
            ) );
            foreach ( $terms as $term ) {
                $term_ids[] = $term->term_id;
            }
        }

        $query = new \WP_Query( array(
            'post_type' => 'any',
            's' => basename( get_attached_file( $attachment_id ) )
        ) );
        while ( $query->have_posts() ) {
            $query->the_post();
            if ( ! in_array( get_the_ID(), $post_ids ) ) {
                $post_ids[] = get_the_ID();
            }
            $categories = get_the_category();
            foreach ( $categories as $category ) {
                if ( ! in_array( $category->term_id, $term_ids ) ) {
                    $term_ids[] = $category->term_id;
                }
            }
            $tags = get_the_tags();
            if ( $tags ) {
                foreach ( $tags as $tag ) {
                    if ( ! in_array( $tag->term_id, $term_ids ) ) {
                        $term_ids[] = $tag->term_id;
                    }
                }
            }
        }
        wp_reset_postdata();

        $meta_query = array(
            'key' => 'term_image_upload_id',
            'value' => $attachment_id,
            'compare' => '='
        );

        $terms_with_meta = get_terms(array(
            'meta_query' => array("relation"=>'OR',$meta_query),
            'taxonomy' => array('category', 'post_tag'),
            'fields' => 'ids'
        ));
        $term_ids = array_merge($term_ids, $terms_with_meta);

        $post_ids = array_unique( $post_ids );
        $term_ids = array_unique( $term_ids );
        $ids = array_merge( $post_ids, $term_ids );

        return $ids;
    }

}