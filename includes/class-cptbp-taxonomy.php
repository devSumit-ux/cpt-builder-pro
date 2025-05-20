<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class CPTBP_Taxonomy {

    public static function register_all() {
        $taxonomies = cptbp_get_all_taxonomies();
        if ( ! empty( $taxonomies ) && is_array( $taxonomies ) ) {
            foreach ( $taxonomies as $slug => $tax_data ) {
                self::register_single_taxonomy( $slug, $tax_data );
            }
        }
    }

    public static function register_single_taxonomy( $slug, $data ) {
        if (empty($data['labels']['singular_name']) || empty($data['labels']['plural_name'])) {
            return;
        }
        if (empty($data['object_types']) || !is_array($data['object_types'])) {
            return; // Must be associated with at least one post type
        }

        $labels = [
            'name'              => esc_html( $data['labels']['plural_name'] ),
            'singular_name'     => esc_html( $data['labels']['singular_name'] ),
            'search_items'      => sprintf( __( 'Search %s', 'cpt-builder-pro' ), esc_html( $data['labels']['plural_name'] ) ),
            'all_items'         => sprintf( __( 'All %s', 'cpt-builder-pro' ), esc_html( $data['labels']['plural_name'] ) ),
            'parent_item'       => sprintf( __( 'Parent %s', 'cpt-builder-pro' ), esc_html( $data['labels']['singular_name'] ) ),
            'parent_item_colon' => sprintf( __( 'Parent %s:', 'cpt-builder-pro' ), esc_html( $data['labels']['singular_name'] ) ),
            'edit_item'         => sprintf( __( 'Edit %s', 'cpt-builder-pro' ), esc_html( $data['labels']['singular_name'] ) ),
            'update_item'       => sprintf( __( 'Update %s', 'cpt-builder-pro' ), esc_html( $data['labels']['singular_name'] ) ),
            'add_new_item'      => sprintf( __( 'Add New %s', 'cpt-builder-pro' ), esc_html( $data['labels']['singular_name'] ) ),
            'new_item_name'     => sprintf( __( 'New %s Name', 'cpt-builder-pro' ), esc_html( $data['labels']['singular_name'] ) ),
            'menu_name'         => esc_html( $data['labels']['plural_name'] ),
            'popular_items'              => sprintf( __( 'Popular %s', 'cpt-builder-pro' ), esc_html( $data['labels']['plural_name'] ) ),
            'separate_items_with_commas' => sprintf( __( 'Separate %s with commas', 'cpt-builder-pro' ), strtolower( esc_html( $data['labels']['plural_name'] ) ) ),
            'add_or_remove_items'        => sprintf( __( 'Add or remove %s', 'cpt-builder-pro' ), strtolower( esc_html( $data['labels']['plural_name'] ) ) ),
            'choose_from_most_used'      => sprintf( __( 'Choose from the most used %s', 'cpt-builder-pro' ), strtolower( esc_html( $data['labels']['plural_name'] ) ) ),
            'not_found'                  => sprintf( __( 'No %s found.', 'cpt-builder-pro' ), strtolower( esc_html( $data['labels']['plural_name'] ) ) ),
            'back_to_items'              => sprintf( __( '← Back to %s', 'cpt-builder-pro' ), strtolower( esc_html( $data['labels']['plural_name'] ) ) ),

        ];

        $args_defaults = [
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => sanitize_title($slug)],
            'show_in_rest'      => false,
        ];
        
        $current_args = isset($data['args']) && is_array($data['args']) ? $data['args'] : [];
        $args = wp_parse_args( $current_args, $args_defaults );
        
        // Specific handling for rewrite slug
        if (isset($current_args['rewrite']) && is_bool($current_args['rewrite']) && !$current_args['rewrite']) {
            $args['rewrite'] = false;
        } elseif (!empty($current_args['rewrite_slug'])) {
            $args['rewrite'] = ['slug' => sanitize_title($current_args['rewrite_slug'])];
        } else {
            $args['rewrite'] = ['slug' => sanitize_title($slug)]; // Default to tax slug
        }

        $args['labels'] = $labels;
        $object_types = array_map( 'sanitize_key', (array) $data['object_types'] );

        register_taxonomy( sanitize_key( $slug ), $object_types, $args );
    }
}