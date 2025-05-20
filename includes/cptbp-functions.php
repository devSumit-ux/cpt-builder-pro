<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

function cptbp_sanitize_boolean_array( $input_array ) {
    $sanitized_array = [];
    if ( is_array( $input_array ) ) {
        foreach ( $input_array as $key => $value ) {
            $s_key = sanitize_key( $key );
            $sanitized_array[ $s_key ] = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
        }
    }
    return $sanitized_array;
}

function cptbp_sanitize_text_field_recursive( $input_data ) {
    if ( is_array( $input_data ) ) {
        $sanitized_array = [];
        foreach ( $input_data as $key => $value ) {
            $s_key = sanitize_key( $key );
            $sanitized_array[ $s_key ] = cptbp_sanitize_text_field_recursive( $value );
        }
        return $sanitized_array;
    } elseif ( is_string( $input_data ) ) {
        return sanitize_text_field( $input_data );
    }
    return $input_data;
}

function cptbp_sanitize_key_array( $input_array ) {
    $sanitized_array = [];
    if ( is_array( $input_array ) ) {
        foreach ( $input_array as $value ) {
            $s_value = sanitize_key( $value );
            if ( ! empty( $s_value ) ) {
                 $sanitized_array[] = $s_value;
            }
        }
    }
    return array_unique( $sanitized_array );
}

function cptbp_get_dashicons_options() {
    return apply_filters('cptbp_dashicons_options', [
        'dashicons-admin-post'      => 'Default (Post)', 'dashicons-admin-media'     => 'Media',
        'dashicons-admin-links'     => 'Links', 'dashicons-admin-comments'  => 'Comments',
        'dashicons-admin-appearance'=> 'Appearance', 'dashicons-admin-plugins'   => 'Plugins',
        'dashicons-admin-users'     => 'Users', 'dashicons-admin-tools'     => 'Tools',
        'dashicons-admin-settings'  => 'Settings', 'dashicons-admin-network'   => 'Network',
        'dashicons-admin-home'      => 'Home / House', 'dashicons-admin-generic'   => 'Generic',
        'dashicons-admin-page'      => 'Page (Single)', 'dashicons-text-page'       => 'Page (Text)',
        'dashicons-id'              => 'ID / Profile', 'dashicons-businessman'     => 'Businessman / Team',
        'dashicons-groups'          => 'Groups / Community', 'dashicons-analytics'       => 'Analytics / Stats',
        'dashicons-chart-pie'       => 'Chart (Pie)', 'dashicons-chart-bar'       => 'Chart (Bar)',
        'dashicons-chart-line'      => 'Chart (Line)', 'dashicons-chart-area'      => 'Chart (Area)',
        'dashicons-camera'          => 'Camera / Photos', 'dashicons-images-alt'      => 'Images (Multiple)',
        'dashicons-images-alt2'     => 'Images (Multiple Alt)', 'dashicons-video-alt3'      => 'Video',
        'dashicons-media-archive'   => 'Media Archive', 'dashicons-portfolio'       => 'Portfolio / Briefcase',
        'dashicons-book'            => 'Book / Documentation', 'dashicons-book-alt'        => 'Book Alt / Journal',
        'dashicons-welcome-learn-more' => 'Learn More / Info', 'dashicons-clipboard'       => 'Clipboard / Tasks',
        'dashicons-calendar'        => 'Calendar / Date', 'dashicons-calendar-alt'    => 'Calendar Alt / Events',
        'dashicons-megaphone'       => 'Megaphone / Announcements', 'dashicons-star-filled'     => 'Star (Filled) / Featured',
        'dashicons-star-half'       => 'Star (Half)', 'dashicons-star-empty'      => 'Star (Empty) / Favorite',
        'dashicons-heart'           => 'Heart / Likes', 'dashicons-awards'          => 'Awards / Badges',
        'dashicons-store'           => 'Store / Shop', 'dashicons-products'        => 'Products / Items',
        'dashicons-cart'            => 'Cart / Basket', 'dashicons-location'        => 'Location Pin',
        'dashicons-location-alt'    => 'Location Pin (Alt)', 'dashicons-building'        => 'Building / Property',
        'dashicons-format-aside'    => 'Format: Aside', 'dashicons-format-chat'     => 'Format: Chat',
        'dashicons-format-gallery'  => 'Format: Gallery', 'dashicons-format-image'    => 'Format: Image',
        'dashicons-format-quote'    => 'Format: Quote', 'dashicons-format-status'   => 'Format: Status',
        'dashicons-format-video'    => 'Format: Video', 'dashicons-format-audio'    => 'Format: Audio',
        'dashicons-hammer'          => 'Hammer / Build', 'dashicons-edit'            => 'Edit / Pencil',
        'dashicons-lightbulb'       => 'Lightbulb / Ideas', 'dashicons-list-view'       => 'List View',
        'dashicons-layout'          => 'Layout / Structure', 'dashicons-feedback'        => 'Feedback / Testimonials',
        'dashicons-shield'          => 'Shield / Security', 'dashicons-sos'             => 'SOS / Support',
        'dashicons-email'           => 'Email / Contact', 'dashicons-phone'           => 'Phone',
        'dashicons-businessperson'  => 'Business Person', 'dashicons-database'        => 'Database',
        'dashicons-archive'         => 'Archive Box', 'dashicons-palmtree'        => 'Palm Tree / Travel',
        'dashicons-tickets-alt'     => 'Tickets',
    ]);
}

function cptbp_debug_log( $log, $context = '' ) {
    if ( defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ) {
        $message = '[' . gmdate( 'Y-m-d H:i:s' ) . ' CPTBP' . ($context ? ' - ' . $context : '') . '] ';
        error_log( $message . print_r( $log, true ) );
    }
}

function cptbp_get_reserved_post_type_slugs() {
    $reserved = [
        'post', 'page', 'attachment', 'revision', 'nav_menu_item', 'custom_css', 
        'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'wp_template', 
        'wp_template_part', 'wp_navigation', 'action_scheduler', 'scheduled-action',
        // Common plugins
        'product', 'product_variation', 'shop_order', 'shop_order_refund', 'shop_coupon', 'shop_webhook', // WooCommerce
        'acf-field-group', 'acf-field', // ACF
        'elementor_library', // Elementor
        'wpcf7_contact_form', // Contact Form 7
    ];
    return apply_filters( 'cptbp_reserved_post_type_slugs', $reserved );
}

function cptbp_get_reserved_taxonomy_slugs() {
    $reserved = [
        'category', 'post_tag', 'nav_menu', 'link_category', 'post_format',
        'wp_theme', 'wp_template_type', 'wp_pattern_category',
        // Common plugins
        'product_cat', 'product_tag', 'product_type', 'product_visibility', 'product_shipping_class', // WooCommerce
    ];
    return apply_filters( 'cptbp_reserved_taxonomy_slugs', $reserved );
}