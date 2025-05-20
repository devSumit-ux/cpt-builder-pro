<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class CPTBP_CPT {

    public static function register_all() {
        $cpts = cptbp_get_all_cpts();
        if ( ! empty( $cpts ) && is_array( $cpts ) ) {
            foreach ( $cpts as $slug => $cpt_data ) {
                self::register_single_cpt( $slug, $cpt_data );
            }
        }
    }

    public static function register_single_cpt( $slug, $data ) {
        if (empty($data['labels']['singular_name']) || empty($data['labels']['plural_name'])) {
            // Basic validation to prevent errors if labels are missing
            return;
        }

        $labels = [
            'name'                  => esc_html( $data['labels']['plural_name'] ),
            'singular_name'         => esc_html( $data['labels']['singular_name'] ),
            'menu_name'             => esc_html( $data['labels']['plural_name'] ),
            'name_admin_bar'        => esc_html( $data['labels']['singular_name'] ),
            'add_new'               => __( 'Add New', 'cpt-builder-pro' ),
            'add_new_item'          => sprintf( __( 'Add New %s', 'cpt-builder-pro' ), esc_html( $data['labels']['singular_name'] ) ),
            'new_item'              => sprintf( __( 'New %s', 'cpt-builder-pro' ), esc_html( $data['labels']['singular_name'] ) ),
            'edit_item'             => sprintf( __( 'Edit %s', 'cpt-builder-pro' ), esc_html( $data['labels']['singular_name'] ) ),
            'view_item'             => sprintf( __( 'View %s', 'cpt-builder-pro' ), esc_html( $data['labels']['singular_name'] ) ),
            'view_items'            => sprintf( __( 'View %s', 'cpt-builder-pro' ), esc_html( $data['labels']['plural_name'] ) ),
            'all_items'             => sprintf( __( 'All %s', 'cpt-builder-pro' ), esc_html( $data['labels']['plural_name'] ) ),
            'search_items'          => sprintf( __( 'Search %s', 'cpt-builder-pro' ), esc_html( $data['labels']['plural_name'] ) ),
            'parent_item_colon'     => sprintf( __( 'Parent %s:', 'cpt-builder-pro' ), esc_html( $data['labels']['singular_name'] ) ),
            'not_found'             => sprintf( __( 'No %s found.', 'cpt-builder-pro' ), strtolower(esc_html($data['labels']['plural_name'])) ),
            'not_found_in_trash'    => sprintf( __( 'No %s found in Trash.', 'cpt-builder-pro' ), strtolower(esc_html($data['labels']['plural_name'])) ),
            'featured_image'        => __( 'Featured Image', 'cpt-builder-pro' ),
            'set_featured_image'    => __( 'Set featured image', 'cpt-builder-pro' ),
            'remove_featured_image' => __( 'Remove featured image', 'cpt-builder-pro' ),
            'use_featured_image'    => __( 'Use as featured image', 'cpt-builder-pro' ),
            'archives'              => esc_html( $data['labels']['plural_name'] ),
            'insert_into_item'      => sprintf( __( 'Insert into %s', 'cpt-builder-pro' ), strtolower(esc_html($data['labels']['singular_name'])) ),
            'uploaded_to_this_item' => sprintf( __( 'Uploaded to this %s', 'cpt-builder-pro' ), strtolower(esc_html($data['labels']['singular_name'])) ),
            'filter_items_list'     => sprintf( __( 'Filter %s list', 'cpt-builder-pro' ), strtolower(esc_html($data['labels']['plural_name'])) ),
            'items_list_navigation' => sprintf( __( '%s list navigation', 'cpt-builder-pro' ), esc_html( $data['labels']['plural_name'] ) ),
            'items_list'            => sprintf( __( '%s list', 'cpt-builder-pro' ), esc_html( $data['labels']['plural_name'] ) ),
        ];

        $args_defaults = [
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => ['slug' => sanitize_title($slug)],
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => ['title', 'editor', 'thumbnail'],
            'show_in_rest'       => false,
            'menu_icon'          => 'dashicons-admin-post',
        ];
        
        $current_args = isset($data['args']) && is_array($data['args']) ? $data['args'] : [];
        $args = wp_parse_args( $current_args, $args_defaults );

        // Specific handling for rewrite slug
        if (isset($current_args['rewrite']) && is_bool($current_args['rewrite']) && !$current_args['rewrite']) {
            $args['rewrite'] = false;
        } elseif (!empty($current_args['rewrite_slug'])) {
            $args['rewrite'] = ['slug' => sanitize_title($current_args['rewrite_slug'])];
        } else {
             $args['rewrite'] = ['slug' => sanitize_title($slug)]; // Default to CPT slug
        }
        if (isset($current_args['has_archive']) && is_string($current_args['has_archive']) && !empty($current_args['has_archive'])) {
            $args['has_archive'] = sanitize_title($current_args['has_archive']);
        }


        $args['labels'] = $labels; // Assign labels to args

        // Sanitize menu_icon (WordPress does this too, but good practice)
        if ($args['menu_icon'] !== 'none' && strpos($args['menu_icon'], 'dashicons-') !== 0 && filter_var($args['menu_icon'], FILTER_VALIDATE_URL)) {
            // It's a URL, ensure it's clean
            $args['menu_icon'] = esc_url_raw($args['menu_icon']);
        } elseif ($args['menu_icon'] !== 'none') {
            $args['menu_icon'] = sanitize_html_class($args['menu_icon']);
        }
        // if menu_position is explicitly empty string from form, convert to null
        if (isset($current_args['menu_position']) && $current_args['menu_position'] === '') {
            $args['menu_position'] = null;
        }


        register_post_type( sanitize_key( $slug ), $args );
    }

    public static function get_available_supports() {
        return [
            'title'           => __( 'Title', 'cpt-builder-pro' ),
            'editor'          => __( 'Editor (Content)', 'cpt-builder-pro' ),
            'author'          => __( 'Author', 'cpt-builder-pro' ),
            'thumbnail'       => __( 'Thumbnail (Featured Image)', 'cpt-builder-pro' ),
            'excerpt'         => __( 'Excerpt', 'cpt-builder-pro' ),
            'trackbacks'      => __( 'Trackbacks', 'cpt-builder-pro' ),
            'custom-fields'   => __( 'Custom Fields (WordPress native)', 'cpt-builder-pro' ),
            'comments'        => __( 'Comments', 'cpt-builder-pro' ),
            'revisions'       => __( 'Revisions', 'cpt-builder-pro' ),
            'page-attributes' => __( 'Page Attributes (for hierarchical)', 'cpt-builder-pro' ),
            'post-formats'    => __( 'Post Formats', 'cpt-builder-pro' ),
        ];
    }
}
