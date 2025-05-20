<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

class CPTBP_Admin {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        
        // Hook into admin_post_ for form submissions
        add_action( 'admin_post_cptbp_save_cpt', [ $this, 'save_cpt_data_handler' ] );
        add_action( 'admin_post_cptbp_save_tax', [ $this, 'save_taxonomy_data_handler' ] );
        
        // Handle deletion links (which are GET requests processed on admin_init or similar early hook)
        add_action( 'admin_init', [ $this, 'handle_delete_actions' ] );
    }

    public function enqueue_scripts( $hook_suffix ) {
        $allowed_hooks = [
            'toplevel_page_cpt-builder-pro',
            'cpt-builder-pro_page_cptbp-post-types',
            'cpt-builder-pro_page_cptbp-add-cpt',
            'admin_page_cptbp-edit-cpt', // Hook for the non-menu edit page
            'cpt-builder-pro_page_cptbp-taxonomies',
            'cpt-builder-pro_page_cptbp-add-taxonomy',
            'admin_page_cptbp-edit-taxonomy', // Hook for the non-menu edit page
            'cpt-builder-pro_page_cptbp-custom-fields',
        ];

        if ( ! in_array( $hook_suffix, $allowed_hooks ) ) {
            return;
        }

        wp_enqueue_style( 'cptbp-admin-style', CPTBP_PLUGIN_URL . 'assets/css/admin-style.css', [], CPTBP_VERSION );
        wp_enqueue_script( 'cptbp-admin-script', CPTBP_PLUGIN_URL . 'assets/js/admin-script.js', ['jquery'], CPTBP_VERSION, true );
        
        $dashicons_options = cptbp_get_dashicons_options();
        wp_localize_script('cptbp-admin-script', 'cptbp_data', [
            'dashicons' => array_keys($dashicons_options),
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cptbp_admin_nonce')
        ]);
    }

    public function add_admin_menu() {
        add_menu_page(
            __( 'CPT Builder Pro', 'cpt-builder-pro' ),
            __( 'CPT Builder Pro', 'cpt-builder-pro' ),
            'manage_options',
            'cpt-builder-pro',
            [ $this, 'render_dashboard_page' ],
            'dashicons-layout',
            25
        );

        add_submenu_page(
            'cpt-builder-pro',
            __( 'Post Types', 'cpt-builder-pro' ),
            __( 'Post Types', 'cpt-builder-pro' ),
            'manage_options',
            'cptbp-post-types',
            [ $this, 'render_post_types_page' ]
        );

        add_submenu_page(
            'cpt-builder-pro', // Parent slug
            __( 'Add New CPT', 'cpt-builder-pro' ),
            __( 'Add New CPT', 'cpt-builder-pro' ),
            'manage_options',
            'cptbp-add-cpt',
            [ $this, 'render_add_edit_cpt_page' ]
        );
        // Non-menu page for editing CPT, so users don't see "Edit Post Type" in menu
        add_submenu_page(
            null, // No parent menu link (makes it hidden)
            __( 'Edit Post Type', 'cpt-builder-pro' ),
            __( 'Edit Post Type', 'cpt-builder-pro' ),
            'manage_options',
            'cptbp-edit-cpt', 
            [ $this, 'render_add_edit_cpt_page' ]
        );
        
        add_submenu_page(
            'cpt-builder-pro',
            __( 'Taxonomies', 'cpt-builder-pro' ),
            __( 'Taxonomies', 'cpt-builder-pro' ),
            'manage_options',
            'cptbp-taxonomies',
            [ $this, 'render_taxonomies_page' ]
        );

        add_submenu_page(
            'cpt-builder-pro', // Parent slug
            __( 'Add New Taxonomy', 'cpt-builder-pro' ),
            __( 'Add New Taxonomy', 'cpt-builder-pro' ),
            'manage_options',
            'cptbp-add-taxonomy',
            [ $this, 'render_add_edit_taxonomy_page' ]
        );
        add_submenu_page(
            null, // No parent menu link
            __( 'Edit Taxonomy', 'cpt-builder-pro' ),
            __( 'Edit Taxonomy', 'cpt-builder-pro' ),
            'manage_options',
            'cptbp-edit-taxonomy',
            [ $this, 'render_add_edit_taxonomy_page' ]
        );

        add_submenu_page(
            'cpt-builder-pro',
            __( 'Custom Fields (ACF)', 'cpt-builder-pro' ),
            __( 'Custom Fields', 'cpt-builder-pro' ),
            'manage_options',
            'cptbp-custom-fields',
            [ $this, 'render_custom_fields_page' ]
        );
    }

    public function render_dashboard_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'CPT Builder Pro Dashboard', 'cpt-builder-pro' ); ?></h1>
            <p><?php esc_html_e( 'Welcome! Manage your custom post types and taxonomies from the menu.', 'cpt-builder-pro' ); ?></p>
            
            <h2><?php esc_html_e( 'Quick Links', 'cpt-builder-pro' ); ?></h2>
            <ul>
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=cptbp-post-types')); ?>"><?php esc_html_e('Manage Post Types', 'cpt-builder-pro'); ?></a></li>
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=cptbp-taxonomies')); ?>"><?php esc_html_e('Manage Taxonomies', 'cpt-builder-pro'); ?></a></li>
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=cptbp-custom-fields')); ?>"><?php esc_html_e('About Custom Fields', 'cpt-builder-pro'); ?></a></li>
            </ul>
        </div>
        <?php
    }

    public function render_post_types_page() {
        $cpts = cptbp_get_all_cpts();
        $page_slug_add = 'cptbp-add-cpt';
        $page_slug_edit = 'cptbp-edit-cpt';
        ?>
        <div class="wrap">
            <h1>
                <?php esc_html_e( 'Custom Post Types', 'cpt-builder-pro' ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $page_slug_add ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'cpt-builder-pro' ); ?></a>
            </h1>

            <?php $this->display_notices(); ?>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Slug', 'cpt-builder-pro' ); ?></th>
                        <th><?php esc_html_e( 'Singular Label', 'cpt-builder-pro' ); ?></th>
                        <th><?php esc_html_e( 'Plural Label', 'cpt-builder-pro' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'cpt-builder-pro' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $cpts ) && is_array($cpts) ) : ?>
                        <?php foreach ( $cpts as $slug => $cpt_data ) : ?>
                            <tr>
                                <td><?php echo esc_html( $slug ); ?></td>
                                <td><?php echo esc_html( $cpt_data['labels']['singular_name'] ); ?></td>
                                <td><?php echo esc_html( $cpt_data['labels']['plural_name'] ); ?></td>
                                <td>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $page_slug_edit . '&action=edit&cpt_slug=' . urlencode( $slug ) ) ); ?>"><?php esc_html_e( 'Edit', 'cpt-builder-pro' ); ?></a> |
                                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=cptbp-post-types&cptbp_action=delete_cpt&cpt_slug=' . urlencode( $slug ) ), 'cptbp_delete_cpt_' . $slug, '_cptbp_nonce' ) ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this post type? This cannot be undone.', 'cpt-builder-pro' ); ?>');" style="color:red;"><?php esc_html_e( 'Delete', 'cpt-builder-pro' ); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4"><?php esc_html_e( 'No custom post types found.', 'cpt-builder-pro' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_add_edit_cpt_page() {
        $current_screen = get_current_screen();
        $action = ($current_screen->id === 'admin_page_cptbp-edit-cpt' && isset($_GET['action']) && $_GET['action'] === 'edit') ? 'edit' : 'add';
        
        $cpt_slug_to_edit = ($action === 'edit' && isset( $_GET['cpt_slug'] )) ? sanitize_key( $_GET['cpt_slug'] ) : null;
        $cpt_data = $cpt_slug_to_edit ? cptbp_get_cpt( $cpt_slug_to_edit ) : [];
        $is_editing = ! empty( $cpt_data );

        $defaults = [
            'labels' => ['singular_name' => '', 'plural_name' => ''],
            'args' => [
                'public' => true, 'show_ui' => true, 'has_archive' => true, 'hierarchical' => false,
                'rewrite' => true, 'rewrite_slug' => '', 'menu_position' => null,
                'menu_icon' => 'dashicons-admin-post', 'supports' => ['title', 'editor', 'thumbnail'],
                'show_in_rest' => true,
            ]
        ];
        $cpt_data = array_replace_recursive( $defaults, $cpt_data );

        $dashicons = cptbp_get_dashicons_options();
        $current_menu_icon = $cpt_data['args']['menu_icon'];
        $is_custom_icon_selected = !array_key_exists($current_menu_icon, $dashicons) && $current_menu_icon !== 'none' && (strpos($current_menu_icon, 'dashicons-') !== 0 || empty($current_menu_icon) );

        ?>
        <div class="wrap">
            <h1><?php echo $is_editing ? esc_html__( 'Edit Post Type', 'cpt-builder-pro' ) : esc_html__( 'Add New Post Type', 'cpt-builder-pro' ); ?></h1>
            <?php $this->display_notices(); ?>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="cptbp_save_cpt">
                <input type="hidden" name="cptbp_form_action_type" value="<?php echo $is_editing ? 'edit_cpt' : 'add_cpt'; ?>">
                <?php if ( $is_editing ) : ?>
                    <input type="hidden" name="cpt_slug_original" value="<?php echo esc_attr( $cpt_slug_to_edit ); ?>">
                <?php endif; ?>
                <?php wp_nonce_field( 'cptbp_save_cpt_nonce_action', '_cptbp_nonce' ); ?>

                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="cpt_slug"><?php esc_html_e( 'Post Type Slug', 'cpt-builder-pro' ); ?></label></th>
                            <td>
                                <input name="cpt_slug" type="text" id="cpt_slug" value="<?php echo esc_attr( $is_editing ? $cpt_slug_to_edit : '' ); ?>" class="regular-text" <?php echo $is_editing ? 'readonly' : ''; ?> required pattern="[a-z0-9_]+" title="<?php esc_attr_e('Lowercase letters, numbers, and underscores only. Max 20 chars.', 'cpt-builder-pro');?>" maxlength="20">
                                <p class="description"><?php esc_html_e( 'Unique identifier (e.g., "movie"). Cannot be changed after creation. Max 20 characters, lowercase alphanumeric and underscores only.', 'cpt-builder-pro' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Labels', 'cpt-builder-pro' ); ?></th>
                            <td>
                                <label for="singular_name"><?php esc_html_e( 'Singular Name', 'cpt-builder-pro' ); ?></label><br>
                                <input name="labels[singular_name]" type="text" id="singular_name" value="<?php echo esc_attr( $cpt_data['labels']['singular_name'] ); ?>" class="regular-text" required><br><br>
                                <label for="plural_name"><?php esc_html_e( 'Plural Name', 'cpt-builder-pro' ); ?></label><br>
                                <input name="labels[plural_name]" type="text" id="plural_name" value="<?php echo esc_attr( $cpt_data['labels']['plural_name'] ); ?>" class="regular-text" required>
                                <p class="description"><?php esc_html_e( 'E.g., Singular: "Movie", Plural: "Movies".', 'cpt-builder-pro' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Settings (Arguments)', 'cpt-builder-pro' ); ?></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><span><?php esc_html_e('Basic Settings', 'cpt-builder-pro');?></span></legend>
                                    <label><input name="args[public]" type="checkbox" value="1" <?php checked( $cpt_data['args']['public'], true ); ?>> <?php esc_html_e( 'Public', 'cpt-builder-pro' ); ?></label><br>
                                    <label><input name="args[show_ui]" type="checkbox" value="1" <?php checked( $cpt_data['args']['show_ui'], true ); ?>> <?php esc_html_e( 'Show UI', 'cpt-builder-pro' ); ?></label><br>
                                    <label><input name="args[hierarchical]" type="checkbox" value="1" <?php checked( $cpt_data['args']['hierarchical'], true ); ?>> <?php esc_html_e( 'Hierarchical', 'cpt-builder-pro' ); ?></label><br>
                                    <label><input name="args[has_archive]" type="checkbox" value="1" <?php checked( $cpt_data['args']['has_archive'], true ); ?>> <?php esc_html_e( 'Has Archive', 'cpt-builder-pro' ); ?></label><br>
                                    <label><input name="args[rewrite]" type="checkbox" value="1" id="cpt_rewrite_checkbox" <?php checked( $cpt_data['args']['rewrite'], true ); ?>> <?php esc_html_e( 'Rewrite', 'cpt-builder-pro' ); ?></label>
                                    <div id="cpt_rewrite_slug_container" style="padding-left: 20px;">
                                       <label for="rewrite_slug"><?php esc_html_e('Custom Rewrite Slug (optional):', 'cpt-builder-pro');?></label>
                                       <input name="args[rewrite_slug]" type="text" id="rewrite_slug" value="<?php echo esc_attr($cpt_data['args']['rewrite_slug']);?>" placeholder="<?php esc_attr_e('e.g., movies', 'cpt-builder-pro');?>" class="regular-text">
                                    </div><br>
                                    <label><input name="args[show_in_rest]" type="checkbox" value="1" <?php checked( $cpt_data['args']['show_in_rest'], true ); ?>> <?php esc_html_e( 'Show in REST API', 'cpt-builder-pro' ); ?></label><br>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="menu_icon_select"><?php esc_html_e( 'Menu Icon', 'cpt-builder-pro' ); ?></label></th>
                            <td>
                                <select name="args[menu_icon_select]" id="menu_icon_select">
                                    <?php foreach ( $dashicons as $class => $label ) : ?>
                                        <option value="<?php echo esc_attr( $class ); ?>" <?php selected( $current_menu_icon, $class ); ?>>
                                            <?php echo esc_html( $label ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="custom" <?php selected( $is_custom_icon_selected, true); ?>>
                                        <?php esc_html_e('Custom URL or non-listed Dashicon', 'cpt-builder-pro'); ?>
                                    </option>
                                     <option value="none" <?php selected( $current_menu_icon, 'none' ); ?>>
                                        <?php esc_html_e('None (no icon)', 'cpt-builder-pro'); ?>
                                    </option>
                                </select>
                                <input name="args[menu_icon_custom]" type="text" id="menu_icon_custom" 
                                       value="<?php echo $is_custom_icon_selected ? esc_attr( $current_menu_icon ) : ''; ?>" 
                                       class="regular-text" 
                                       placeholder="<?php esc_attr_e('Enter Dashicon class or full URL', 'cpt-builder-pro');?>">
                                <p class="description"><?php esc_html_e( 'Select a Dashicon, or choose "Custom" to enter a class name or image URL. "None" removes the icon.', 'cpt-builder-pro' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="menu_position"><?php esc_html_e( 'Menu Position', 'cpt-builder-pro' ); ?></label></th>
                            <td>
                                <input name="args[menu_position]" type="number" step="1" id="menu_position" value="<?php echo esc_attr( $cpt_data['args']['menu_position'] ); ?>" class="small-text">
                                <p class="description"><?php esc_html_e( 'Position in admin menu. E.g., 5 (below Posts). Leave blank for default.', 'cpt-builder-pro' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Supports (Editor Features)', 'cpt-builder-pro' ); ?></th>
                            <td><fieldset>
                                <?php
                                $available_supports = CPTBP_CPT::get_available_supports();
                                $current_supports = (array) $cpt_data['args']['supports'];
                                foreach ( $available_supports as $support_key => $support_label ) : ?>
                                    <label><input name="args[supports][]" type="checkbox" value="<?php echo esc_attr( $support_key ); ?>" <?php checked( in_array( $support_key, $current_supports, true ) ); ?>> <?php echo esc_html( $support_label ); ?></label><br>
                                <?php endforeach; ?>
                            </fieldset></td>
                        </tr>
                    </tbody>
                </table>
                <?php submit_button( $is_editing ? __( 'Save Changes', 'cpt-builder-pro' ) : __( 'Add Post Type', 'cpt-builder-pro' ) ); ?>
            </form>
        </div>
        <?php
    }

    public function render_taxonomies_page() {
        $taxonomies = cptbp_get_all_taxonomies();
        $page_slug_add = 'cptbp-add-taxonomy';
        $page_slug_edit = 'cptbp-edit-taxonomy';
        ?>
         <div class="wrap">
            <h1>
                <?php esc_html_e( 'Custom Taxonomies', 'cpt-builder-pro' ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $page_slug_add ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'cpt-builder-pro' ); ?></a>
            </h1>
            <?php $this->display_notices(); ?>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr>
                    <th><?php esc_html_e( 'Slug', 'cpt-builder-pro' ); ?></th>
                    <th><?php esc_html_e( 'Singular Label', 'cpt-builder-pro' ); ?></th>
                    <th><?php esc_html_e( 'Plural Label', 'cpt-builder-pro' ); ?></th>
                    <th><?php esc_html_e( 'Attached to', 'cpt-builder-pro' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'cpt-builder-pro' ); ?></th>
                </tr></thead>
                <tbody>
                    <?php if ( ! empty( $taxonomies ) && is_array($taxonomies) ) : ?>
                        <?php foreach ( $taxonomies as $slug => $tax_data ) : ?>
                            <tr>
                                <td><?php echo esc_html( $slug ); ?></td>
                                <td><?php echo esc_html( $tax_data['labels']['singular_name'] ); ?></td>
                                <td><?php echo esc_html( $tax_data['labels']['plural_name'] ); ?></td>
                                <td><?php echo esc_html( implode(', ', array_map('ucfirst', (array)$tax_data['object_types'])) ); ?></td>
                                <td>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $page_slug_edit . '&action=edit&tax_slug=' . urlencode( $slug ) ) ); ?>"><?php esc_html_e( 'Edit', 'cpt-builder-pro' ); ?></a> |
                                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=cptbp-taxonomies&cptbp_action=delete_tax&tax_slug=' . urlencode( $slug ) ), 'cptbp_delete_tax_' . $slug, '_cptbp_nonce' ) ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this taxonomy? This cannot be undone.', 'cpt-builder-pro' ); ?>');" style="color:red;"><?php esc_html_e( 'Delete', 'cpt-builder-pro' ); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="5"><?php esc_html_e( 'No custom taxonomies found.', 'cpt-builder-pro' ); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_add_edit_taxonomy_page() {
        $current_screen = get_current_screen();
        $action = ($current_screen->id === 'admin_page_cptbp-edit-taxonomy' && isset($_GET['action']) && $_GET['action'] === 'edit') ? 'edit' : 'add';

        $tax_slug_to_edit = ($action === 'edit' && isset( $_GET['tax_slug'] )) ? sanitize_key( $_GET['tax_slug'] ) : null;
        $tax_data = $tax_slug_to_edit ? cptbp_get_taxonomy( $tax_slug_to_edit ) : [];
        $is_editing = ! empty( $tax_data );

        $defaults = [
            'labels' => ['singular_name' => '', 'plural_name' => ''],
            'object_types' => ['post'],
            'args' => [
                'hierarchical' => false, 'public' => true, 'show_ui' => true,
                'show_admin_column' => true, 'rewrite' => true, 'rewrite_slug' => '',
                'show_in_rest' => true,
            ]
        ];
        $tax_data = array_replace_recursive($defaults, $tax_data);

        $all_post_types = get_post_types( [ 'show_ui' => true ], 'objects' );
        $plugin_cpts_data = cptbp_get_all_cpts();
        
        ?>
        <div class="wrap">
            <h1><?php echo $is_editing ? esc_html__( 'Edit Taxonomy', 'cpt-builder-pro' ) : esc_html__( 'Add New Taxonomy', 'cpt-builder-pro' ); ?></h1>
            <?php $this->display_notices(); ?>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="cptbp_save_tax">
                <input type="hidden" name="cptbp_form_action_type" value="<?php echo $is_editing ? 'edit_tax' : 'add_tax'; ?>">
                <?php if ( $is_editing ) : ?>
                    <input type="hidden" name="tax_slug_original" value="<?php echo esc_attr( $tax_slug_to_edit ); ?>">
                <?php endif; ?>
                <?php wp_nonce_field( 'cptbp_save_tax_nonce_action', '_cptbp_nonce' ); ?>

                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="tax_slug"><?php esc_html_e( 'Taxonomy Slug', 'cpt-builder-pro' ); ?></label></th>
                            <td>
                                <input name="tax_slug" type="text" id="tax_slug" value="<?php echo esc_attr( $is_editing ? $tax_slug_to_edit : '' ); ?>" class="regular-text" <?php echo $is_editing ? 'readonly' : ''; ?> required pattern="[a-z0-9_]+" title="<?php esc_attr_e('Lowercase letters, numbers, and underscores only. Max 32 chars.', 'cpt-builder-pro');?>" maxlength="32">
                                <p class="description"><?php esc_html_e( 'Unique identifier (e.g., "genre"). Cannot be changed. Max 32 characters.', 'cpt-builder-pro' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Labels', 'cpt-builder-pro' ); ?></th>
                            <td>
                                <label for="singular_name_tax"><?php esc_html_e( 'Singular Name', 'cpt-builder-pro' ); ?></label><br>
                                <input name="labels[singular_name]" type="text" id="singular_name_tax" value="<?php echo esc_attr( $tax_data['labels']['singular_name'] ); ?>" class="regular-text" required><br><br>
                                <label for="plural_name_tax"><?php esc_html_e( 'Plural Name', 'cpt-builder-pro' ); ?></label><br>
                                <input name="labels[plural_name]" type="text" id="plural_name_tax" value="<?php echo esc_attr( $tax_data['labels']['plural_name'] ); ?>" class="regular-text" required>
                                <p class="description"><?php esc_html_e( 'E.g., Singular: "Genre", Plural: "Genres".', 'cpt-builder-pro' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Attach to Post Type(s)', 'cpt-builder-pro' ); ?></th>
                            <td><fieldset>
                                <?php
                                $available_pts_for_tax = [];
                                if (is_array($all_post_types)) {
                                    foreach ($all_post_types as $pt_slug => $pt_object) {
                                        if ($pt_slug === 'attachment') continue;
                                        $available_pts_for_tax[$pt_slug] = $pt_object->label . (isset($plugin_cpts_data[$pt_slug]) ? ' (CPTBP)' : '');
                                    }
                                }
                                if (is_array($plugin_cpts_data)) {
                                    foreach ($plugin_cpts_data as $pcpt_slug => $pcpt_def) {
                                        if (!isset($available_pts_for_tax[$pcpt_slug])) {
                                            $available_pts_for_tax[$pcpt_slug] = $pcpt_def['labels']['plural_name'] . ' (CPTBP - Pending)';
                                        }
                                    }
                                }
                                asort($available_pts_for_tax);

                                if (empty($available_pts_for_tax)) {
                                    echo '<p>' . esc_html__('No suitable post types found or created yet.', 'cpt-builder-pro') . '</p>';
                                } else {
                                    foreach ( $available_pts_for_tax as $pt_key => $pt_label ) {
                                        echo '<label><input name="object_types[]" type="checkbox" value="' . esc_attr( $pt_key ) . '" ' . checked( in_array( $pt_key, (array)$tax_data['object_types'], true ), true, false ) . '> ' . esc_html( $pt_label ) . '</label><br>';
                                    }
                                }
                                ?>
                            </fieldset></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Settings (Arguments)', 'cpt-builder-pro' ); ?></th>
                            <td><fieldset>
                                    <label><input name="args[hierarchical]" type="checkbox" value="1" <?php checked( $tax_data['args']['hierarchical'], true ); ?>> <?php esc_html_e( 'Hierarchical', 'cpt-builder-pro' ); ?></label><br>
                                    <label><input name="args[public]" type="checkbox" value="1" <?php checked( $tax_data['args']['public'], true ); ?>> <?php esc_html_e( 'Public', 'cpt-builder-pro' ); ?></label><br>
                                    <label><input name="args[show_ui]" type="checkbox" value="1" <?php checked( $tax_data['args']['show_ui'], true ); ?>> <?php esc_html_e( 'Show UI', 'cpt-builder-pro' ); ?></label><br>
                                    <label><input name="args[show_admin_column]" type="checkbox" value="1" <?php checked( $tax_data['args']['show_admin_column'], true ); ?>> <?php esc_html_e( 'Show Admin Column', 'cpt-builder-pro' ); ?></label><br>
                                    <label><input name="args[rewrite]" type="checkbox" value="1" id="tax_rewrite_checkbox" <?php checked( $tax_data['args']['rewrite'], true ); ?>> <?php esc_html_e( 'Rewrite', 'cpt-builder-pro' ); ?></label>
                                     <div id="tax_rewrite_slug_container" style="padding-left: 20px;">
                                       <label for="tax_rewrite_slug"><?php esc_html_e('Custom Rewrite Slug (optional):', 'cpt-builder-pro');?></label>
                                       <input name="args[rewrite_slug]" type="text" id="tax_rewrite_slug" value="<?php echo esc_attr($tax_data['args']['rewrite_slug']);?>" placeholder="<?php esc_attr_e('e.g., movie-genres', 'cpt-builder-pro');?>" class="regular-text">
                                    </div><br>
                                    <label><input name="args[show_in_rest]" type="checkbox" value="1" <?php checked( $tax_data['args']['show_in_rest'], true ); ?>> <?php esc_html_e( 'Show in REST API', 'cpt-builder-pro' ); ?></label><br>
                            </fieldset></td>
                        </tr>
                    </tbody>
                </table>
                <?php submit_button( $is_editing ? __( 'Save Changes', 'cpt-builder-pro' ) : __( 'Add Taxonomy', 'cpt-builder-pro' ) ); ?>
            </form>
        </div>
        <?php
    }

    public function render_custom_fields_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Advanced Custom Fields Integration', 'cpt-builder-pro' ); ?></h1>
            <p><?php esc_html_e( 'CPT Builder Pro helps you create the structure for your content (Custom Post Types and Taxonomies). To add custom fields like text inputs, image galleries, repeaters, etc., we recommend using the powerful Advanced Custom Fields (ACF) plugin.', 'cpt-builder-pro' ); ?></p>
            <h2><?php esc_html_e( 'How it Works:', 'cpt-builder-pro' ); ?></h2>
            <ol>
                <li><?php esc_html_e( 'Create your Custom Post Types and Taxonomies using CPT Builder Pro.', 'cpt-builder-pro' ); ?></li>
                <li>
                    <?php
                    if ( class_exists('ACF') || function_exists('acf_add_local_field_group') ) { // Check for ACF Free or Pro
                        printf(
                            wp_kses_post( __( 'Go to <a href="%s">Custom Fields &rarr; Field Groups</a> to create and assign field groups to your new post types.', 'cpt-builder-pro' ) ),
                            esc_url(admin_url('edit.php?post_type=acf-field-group'))
                        );
                    } else {
                        printf(
                            wp_kses_post( __( 'Install and activate the <a href="%s" target="_blank">Advanced Custom Fields</a> plugin (free or Pro). Then, go to Custom Fields &rarr; Field Groups to assign fields to your post types.', 'cpt-builder-pro' ) ),
                            esc_url('https://wordpress.org/plugins/advanced-custom-fields/')
                        );
                    }
                    ?>
                </li>
                <li><?php esc_html_e( 'In ACF, when creating a Field Group, set the "Location Rules" to show the field group if "Post Type" is equal to your custom post type.', 'cpt-builder-pro' ); ?></li>
            </ol>
            <p><em><?php esc_html_e( 'Future versions of CPT Builder Pro may include tighter integration with ACF.', 'cpt-builder-pro' ); ?></em></p>
            <h2><?php esc_html_e( 'Why use ACF?', 'cpt-builder-pro' ); ?></h2>
            <p><?php esc_html_e( 'ACF is a mature, feature-rich, and widely adopted plugin for custom fields, offering:', 'cpt-builder-pro' ); ?></p>
            <ul>
                <li><?php esc_html_e( 'A vast array of field types.', 'cpt-builder-pro' ); ?></li>
                <li><?php esc_html_e( 'Intuitive field group builder.', 'cpt-builder-pro' ); ?></li>
                <li><?php esc_html_e( 'Excellent developer API and extensive documentation.', 'cpt-builder-pro' ); ?></li>
            </ul>
        </div>
        <?php
    }

    public function handle_delete_actions() {
        if ( ! isset( $_GET['cptbp_action'] ) || ! isset( $_GET['_cptbp_nonce'] ) ) {
            return;
        }

        $action = sanitize_key( $_GET['cptbp_action'] );

        if ( $action === 'delete_cpt' && isset($_GET['cpt_slug']) && wp_verify_nonce( $_GET['_cptbp_nonce'], 'cptbp_delete_cpt_' . $_GET['cpt_slug'] ) ) {
            $this->delete_cpt_data( sanitize_key($_GET['cpt_slug']) );
        } elseif ( $action === 'delete_tax' && isset($_GET['tax_slug']) && wp_verify_nonce( $_GET['_cptbp_nonce'], 'cptbp_delete_tax_' . $_GET['tax_slug'] ) ) {
            $this->delete_taxonomy_data( sanitize_key($_GET['tax_slug']) );
        }
    }
    
    public function save_cpt_data_handler() {
        if ( ! isset( $_POST['_cptbp_nonce'] ) || ! wp_verify_nonce( $_POST['_cptbp_nonce'], 'cptbp_save_cpt_nonce_action' ) ) {
            wp_die( __('Security check failed!', 'cpt-builder-pro') );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to perform this action.', 'cpt-builder-pro' ) );
        }

        $form_action_type = isset($_POST['cptbp_form_action_type']) ? sanitize_key($_POST['cptbp_form_action_type']) : '';
        $is_editing = ($form_action_type === 'edit_cpt');
        $original_slug = $is_editing && isset( $_POST['cpt_slug_original'] ) ? sanitize_key( $_POST['cpt_slug_original'] ) : null;
        $cpt_slug = isset( $_POST['cpt_slug'] ) ? sanitize_key( $_POST['cpt_slug'] ) : '';

        $errors = [];
        if ( empty( $cpt_slug ) ) { $errors[] = __('Post Type Slug cannot be empty.', 'cpt-builder-pro'); }
        elseif ( strlen($cpt_slug) > 20 ) { $errors[] = __('Post Type Slug cannot exceed 20 characters.', 'cpt-builder-pro');}
        elseif ( ! preg_match( '/^[a-z0-9_]+$/', $cpt_slug ) ) { $errors[] = __('Post Type Slug: lowercase alphanumeric and underscores only.', 'cpt-builder-pro');}
        elseif ( ! $is_editing ) {
            if ( in_array( $cpt_slug, cptbp_get_reserved_post_type_slugs(), true ) ) {
                $errors[] = sprintf(__('The slug "%s" is reserved. Please choose a different slug.', 'cpt-builder-pro'), $cpt_slug);
            }
            if ( isset(cptbp_get_all_cpts()[$cpt_slug] ) || post_type_exists($cpt_slug) ) {
                 $errors[] = __('A post type with this slug already exists.', 'cpt-builder-pro');
            }
        }

        $labels_input = isset( $_POST['labels'] ) && is_array( $_POST['labels'] ) ? $_POST['labels'] : [];
        $labels = array_map( 'sanitize_text_field', $labels_input );
        if ( empty( $labels['singular_name'] ) || empty( $labels['plural_name'] ) ) {
             $errors[] = __('Singular and Plural labels are required.', 'cpt-builder-pro');
        }

        if ( ! empty( $errors ) ) {
            foreach ($errors as $error) { add_settings_error('cptbp_messages', 'cpt_error', $error, 'error'); }
            set_transient('settings_errors', get_settings_errors(), 30);
            $redirect_page = $is_editing ? 'cptbp-edit-cpt&action=edit&cpt_slug=' . urlencode($original_slug) : 'cptbp-add-cpt';
            wp_safe_redirect( admin_url( 'admin.php?page=' . $redirect_page ) );
            exit;
        }

        $args_input = isset( $_POST['args'] ) && is_array( $_POST['args'] ) ? $_POST['args'] : [];
        $sanitized_args = [
            'public'             => isset($args_input['public']) ? filter_var($args_input['public'], FILTER_VALIDATE_BOOLEAN) : false,
            'show_ui'            => isset($args_input['show_ui']) ? filter_var($args_input['show_ui'], FILTER_VALIDATE_BOOLEAN) : false,
            'hierarchical'       => isset($args_input['hierarchical']) ? filter_var($args_input['hierarchical'], FILTER_VALIDATE_BOOLEAN) : false,
            'has_archive'        => isset($args_input['has_archive']) ? filter_var($args_input['has_archive'], FILTER_VALIDATE_BOOLEAN) : false,
            'rewrite'            => isset($args_input['rewrite']) ? filter_var($args_input['rewrite'], FILTER_VALIDATE_BOOLEAN) : false,
            'rewrite_slug'       => isset($args_input['rewrite_slug']) ? sanitize_title($args_input['rewrite_slug']) : '',
            'menu_position'      => isset($args_input['menu_position']) && $args_input['menu_position'] !== '' ? intval($args_input['menu_position']) : null,
            'supports'           => isset($args_input['supports']) && is_array($args_input['supports']) ? cptbp_sanitize_key_array($args_input['supports']) : ['title', 'editor'],
            'show_in_rest'       => isset($args_input['show_in_rest']) ? filter_var($args_input['show_in_rest'], FILTER_VALIDATE_BOOLEAN) : false,
            'publicly_queryable' => isset($args_input['public']) ? filter_var($args_input['public'], FILTER_VALIDATE_BOOLEAN) : true,
            'show_in_menu'       => isset($args_input['show_ui']) ? filter_var($args_input['show_ui'], FILTER_VALIDATE_BOOLEAN) : true,
            'query_var'          => true, 'capability_type'    => 'post',
        ];
        
        if (isset($args_input['menu_icon_select'])) {
            if ($args_input['menu_icon_select'] === 'custom' && !empty($args_input['menu_icon_custom'])) {
                $sanitized_args['menu_icon'] = esc_url_raw(trim($args_input['menu_icon_custom'])); // Allow URLs or Dashicon class
                 if (strpos($sanitized_args['menu_icon'], 'dashicons-') === 0) { // If it's a dashicon class
                    $sanitized_args['menu_icon'] = sanitize_html_class($sanitized_args['menu_icon']);
                 }
            } elseif ($args_input['menu_icon_select'] === 'none') {
                $sanitized_args['menu_icon'] = 'none';
            } else {
                $sanitized_args['menu_icon'] = sanitize_html_class($args_input['menu_icon_select']);
            }
        } else { $sanitized_args['menu_icon'] = 'dashicons-admin-post'; }

        $cpt_data = [ 'labels' => $labels, 'args'   => $sanitized_args ];
        $all_cpts = cptbp_get_all_cpts();

        if ( $is_editing && $original_slug ) { $all_cpts[ $original_slug ] = $cpt_data; }
        else { $all_cpts[ $cpt_slug ] = $cpt_data; }

        cptbp_save_all_cpts( $all_cpts );
        add_settings_error('cptbp_messages', 'cpt_saved', $is_editing ? __('Post Type updated.', 'cpt-builder-pro') : __('Post Type added.', 'cpt-builder-pro'), 'updated');
        set_transient('settings_errors', get_settings_errors(), 30);
        flush_rewrite_rules();
        wp_safe_redirect( admin_url( 'admin.php?page=cptbp-post-types' ) );
        exit;
    }
    
    private function delete_cpt_data( $cpt_slug_to_delete ) {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( __('Not allowed.', 'cpt-builder-pro') ); }
        if (cptbp_delete_cpt( $cpt_slug_to_delete ) ) {
            add_settings_error('cptbp_messages', 'cpt_deleted', __('Post Type deleted.', 'cpt-builder-pro'), 'updated');
            flush_rewrite_rules();
        } else { add_settings_error('cptbp_messages', 'cpt_delete_failed', __('Failed to delete post type.', 'cpt-builder-pro'), 'error');}
        set_transient('settings_errors', get_settings_errors(), 30);
        wp_safe_redirect( admin_url( 'admin.php?page=cptbp-post-types' ) );
        exit;
    }

    public function save_taxonomy_data_handler() {
        if ( ! isset( $_POST['_cptbp_nonce'] ) || ! wp_verify_nonce( $_POST['_cptbp_nonce'], 'cptbp_save_tax_nonce_action' ) ) {
            wp_die( __('Security check failed!', 'cpt-builder-pro') );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to perform this action.', 'cpt-builder-pro' ) );
        }

        $form_action_type = isset($_POST['cptbp_form_action_type']) ? sanitize_key($_POST['cptbp_form_action_type']) : '';
        $is_editing = ($form_action_type === 'edit_tax');
        $original_slug = $is_editing && isset( $_POST['tax_slug_original'] ) ? sanitize_key( $_POST['tax_slug_original'] ) : null;
        $tax_slug = isset( $_POST['tax_slug'] ) ? sanitize_key( $_POST['tax_slug'] ) : '';
        
        $errors = [];
        if ( empty( $tax_slug ) ) { $errors[] = __('Taxonomy Slug cannot be empty.', 'cpt-builder-pro'); }
        elseif ( strlen($tax_slug) > 32 ) { $errors[] = __('Taxonomy Slug cannot exceed 32 characters.', 'cpt-builder-pro'); }
        elseif ( ! preg_match( '/^[a-z0-9_]+$/', $tax_slug ) ) { $errors[] = __('Taxonomy Slug: lowercase alphanumeric and underscores only.', 'cpt-builder-pro'); }
        elseif ( ! $is_editing ) {
            if ( in_array( $tax_slug, cptbp_get_reserved_taxonomy_slugs(), true ) ) {
                $errors[] = sprintf(__('The slug "%s" is reserved. Please choose a different slug.', 'cpt-builder-pro'), $tax_slug);
            }
            if ( isset(cptbp_get_all_taxonomies()[$tax_slug] ) || taxonomy_exists($tax_slug) ) {
                 $errors[] = __('A taxonomy with this slug already exists.', 'cpt-builder-pro');
            }
        }
        
        $labels_input = isset( $_POST['labels'] ) && is_array( $_POST['labels'] ) ? $_POST['labels'] : [];
        $labels = array_map( 'sanitize_text_field', $labels_input );
        if ( empty( $labels['singular_name'] ) || empty( $labels['plural_name'] ) ) { $errors[] = __('Singular and Plural labels are required.', 'cpt-builder-pro'); }

        $object_types_input = isset( $_POST['object_types'] ) && is_array( $_POST['object_types'] ) ? $_POST['object_types'] : [];
        $object_types = cptbp_sanitize_key_array( $object_types_input );
        if ( empty( $object_types ) ) { $errors[] = __('Taxonomy must be attached to at least one post type.', 'cpt-builder-pro'); }

        if ( ! empty( $errors ) ) {
            foreach ($errors as $error) { add_settings_error('cptbp_messages', 'tax_error', $error, 'error');}
            set_transient('settings_errors', get_settings_errors(), 30);
            $redirect_page = $is_editing ? 'cptbp-edit-taxonomy&action=edit&tax_slug=' . urlencode($original_slug) : 'cptbp-add-taxonomy';
            wp_safe_redirect( admin_url( 'admin.php?page=' . $redirect_page ) );
            exit;
        }

        $args_input = isset( $_POST['args'] ) && is_array( $_POST['args'] ) ? $_POST['args'] : [];
        $sanitized_args = [
            'hierarchical'      => isset($args_input['hierarchical']) ? filter_var($args_input['hierarchical'], FILTER_VALIDATE_BOOLEAN) : false,
            'public'            => isset($args_input['public']) ? filter_var($args_input['public'], FILTER_VALIDATE_BOOLEAN) : true,
            'show_ui'           => isset($args_input['show_ui']) ? filter_var($args_input['show_ui'], FILTER_VALIDATE_BOOLEAN) : true,
            'show_admin_column' => isset($args_input['show_admin_column']) ? filter_var($args_input['show_admin_column'], FILTER_VALIDATE_BOOLEAN) : true,
            'rewrite'           => isset($args_input['rewrite']) ? filter_var($args_input['rewrite'], FILTER_VALIDATE_BOOLEAN) : true,
            'rewrite_slug'      => isset($args_input['rewrite_slug']) ? sanitize_title($args_input['rewrite_slug']) : '',
            'show_in_rest'      => isset($args_input['show_in_rest']) ? filter_var($args_input['show_in_rest'], FILTER_VALIDATE_BOOLEAN) : false,
            'query_var'         => true,
        ];

        $tax_data = [ 'labels' => $labels, 'object_types' => $object_types, 'args' => $sanitized_args ];
        $all_taxonomies = cptbp_get_all_taxonomies();

        if ( $is_editing && $original_slug ) { $all_taxonomies[ $original_slug ] = $tax_data; }
        else { $all_taxonomies[ $tax_slug ] = $tax_data; }

        cptbp_save_all_taxonomies( $all_taxonomies );
        add_settings_error('cptbp_messages', 'tax_saved', $is_editing ? __('Taxonomy updated.', 'cpt-builder-pro') : __('Taxonomy added.', 'cpt-builder-pro'), 'updated');
        set_transient('settings_errors', get_settings_errors(), 30);
        flush_rewrite_rules();
        wp_safe_redirect( admin_url( 'admin.php?page=cptbp-taxonomies' ) );
        exit;
    }

    private function delete_taxonomy_data( $tax_slug_to_delete ) {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( __('Not allowed.', 'cpt-builder-pro') ); }
        if (cptbp_delete_taxonomy( $tax_slug_to_delete ) ) {
            add_settings_error('cptbp_messages', 'tax_deleted', __('Taxonomy deleted.', 'cpt-builder-pro'), 'updated');
            flush_rewrite_rules();
        } else { add_settings_error('cptbp_messages', 'tax_delete_failed', __('Failed to delete taxonomy.', 'cpt-builder-pro'), 'error');}
        set_transient('settings_errors', get_settings_errors(), 30);
        wp_safe_redirect( admin_url( 'admin.php?page=cptbp-taxonomies' ) );
        exit;
    }

    public function display_notices() {
        $errors = get_transient('settings_errors');
        if ($errors) {
            $output = '';
            foreach ($errors as $error) {
                // Check if it's one of our specific messages or a general WP one we want to show
                if (strpos($error['setting'], 'cptbp_') === 0 || $error['code'] === 'cpt_error' || $error['code'] === 'tax_error' || $error['code'] === 'cpt_saved' || $error['code'] === 'tax_saved' || $error['code'] === 'cpt_deleted' || $error['code'] === 'tax_deleted' || $error['code'] === 'cpt_delete_failed' || $error['code'] === 'tax_delete_failed') {
                    $type_class = $error['type'] === 'error' ? 'notice-error' : 'notice-success';
                    $output .= '<div class="notice ' . esc_attr($type_class) . ' is-dismissible">';
                    $output .= '<p><strong>' . esc_html($error['message']) . '</strong></p>';
                    $output .= '</div>';
                }
            }
            if ($output) {
                 // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $output;
            }
            delete_transient('settings_errors');
        }
    }
}