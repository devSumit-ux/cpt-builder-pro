<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

function cptbp_get_all_cpts() {
    return get_option( CPTBP_CPT_OPTION_NAME, [] );
}

function cptbp_get_cpt( $slug ) {
    $cpts = cptbp_get_all_cpts();
    return isset( $cpts[ $slug ] ) ? $cpts[ $slug ] : false;
}

function cptbp_save_all_cpts( $cpts ) {
    return update_option( CPTBP_CPT_OPTION_NAME, $cpts );
}

function cptbp_delete_cpt( $slug ) {
    $cpts = cptbp_get_all_cpts();
    if ( isset( $cpts[ $slug ] ) ) {
        unset( $cpts[ $slug ] );
        return cptbp_save_all_cpts( $cpts );
    }
    return false;
}

function cptbp_get_all_taxonomies() {
    return get_option( CPTBP_TAX_OPTION_NAME, [] );
}

function cptbp_get_taxonomy( $slug ) {
    $taxonomies = cptbp_get_all_taxonomies();
    return isset( $taxonomies[ $slug ] ) ? $taxonomies[ $slug ] : false;
}

function cptbp_save_all_taxonomies( $taxonomies ) {
    return update_option( CPTBP_TAX_OPTION_NAME, $taxonomies );
}

function cptbp_delete_taxonomy( $slug ) {
    $taxonomies = cptbp_get_all_taxonomies();
    if ( isset( $taxonomies[ $slug ] ) ) {
        unset( $taxonomies[ $slug ] );
        return cptbp_save_all_taxonomies( $taxonomies );
    }
    return false;
}

function cptbp_get_registered_post_types( $args = [], $output_type = 'objects', $operator = 'and' ) {
    $default_args = [ 'public'   => true, '_builtin' => false ];
    $args = wp_parse_args( $args, $default_args );
    return get_post_types( $args, $output_type, $operator );
}

function cptbp_get_registered_taxonomies( $args = [], $output = 'objects', $operator = 'and' ) {
    $default_args = [ 'public'   => true, '_builtin' => false ];
    $args = wp_parse_args( $args, $default_args );
    return get_taxonomies( $args, $output, $operator );
}