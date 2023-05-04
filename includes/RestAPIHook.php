<?php

namespace SafeTermMediaDelete;

class RestAPIHook
{
    public function __construct(){
        add_action( 'rest_api_init', [$this, "register_rest_route_for_assignment"] );
    }

    function register_rest_route_for_assignment() {
        register_rest_route( 'assignment/v1', '/images/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => [$this,'get_image_details'],
        ) );

        register_rest_route( 'assignment/v1', '/images/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_image'],
        ) );
    }

    function get_image_details( $request ) {
        $id = $request->get_param( 'id' );

        $image = get_post( $id );

        if ( empty( $image ) || $image->post_type != 'attachment' ) {
            return new \WP_Error( 'invalid_image_id', 'Invalid image ID', array( 'status' => 404 ) );
        }

        $image_details = array(
            'id' => $image->ID,
            'date' => $image->post_date_gmt,
            'slug' => $image->post_name,
            'type' => $image->post_mime_type,
            'link' => wp_get_attachment_url( $image->ID ),
            'alt_text' => get_post_meta( $image->ID, '_wp_attachment_image_alt', true ),
            'attached_objects' => array(),
        );

        $attached_posts = get_posts( array(
            'post_type' => 'any',
            'meta_key' => '_thumbnail_id',
            'meta_value' => $image->ID,
        ) );
        foreach ( $attached_posts as $post ) {
            $image_details['attached_objects'][] = array(
                'type' => 'post',
                'id' => $post->ID,
            );
        }

        $attached_terms = wp_get_object_terms( $image->ID, 'category' );
        foreach ( $attached_terms as $term ) {
            $image_details['attached_objects'][] = array(
                'type' => 'term',
                'id' => $term->term_id,
            );
        }

        return $image_details;
    }

    function delete_image( $request ) {
        $id = $request->get_param( 'id' );

        $image = get_post( $id );
        if ( empty( $image ) || $image->post_type != 'attachment' ) {
            return new \WP_Error( 'invalid_image_id', 'Invalid image ID', array( 'status' => 404 ) );
        }

        $attached_posts = get_posts( array(
            'post_type' => 'any',
            'meta_key' => '_thumbnail_id',
            'meta_value' => $id,
        ) );
        $attached_terms = wp_get_object_terms( $id, 'category' );

        if ( ! empty( $attached_posts ) || ! empty( $attached_terms ) ) {
            return new \WP_Error( 'image_in_use', 'Cannot delete image - it is attached to one or more posts or terms', array( 'status' => 400 ) );
        }

        $result = wp_delete_attachment( $id, true );
        if ( $result === false ) {
            return new \WP_Error( 'delete_failed', 'Failed to delete image', array( 'status' => 500 ) );
        }

        return array( 'success' => true );
    }
}