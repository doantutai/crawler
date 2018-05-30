<?php
/*
Plugin Name: SSTech WP Crawler
Plugin URI: http://sstechvn.com/
Description: Crawl Any Website Content Into WordPress Posts
Author: WP Crawler Team
Version: 1.0.3
Author URI: http://sstechvn.com/
 */

/*-----------------------------------------------------------------------------------*/
/*	Includes
/*-----------------------------------------------------------------------------------*/
if( ! defined( 'WP_CRAWLER_DIR' ) ) {
    define( 'WP_CRAWLER_DIR', plugin_dir_path( __FILE__ ) );
}

if( ! defined( 'WP_CRAWLER_URL' ) ) {
    define( 'WP_CRAWLER_URL', plugin_dir_url( __FILE__ ) );
}

require_once WP_CRAWLER_DIR . 'libs/cmb2/init.php';
require_once WP_CRAWLER_DIR . 'libs/simplehtmldom/simple_html_dom.php';
require_once WP_CRAWLER_DIR . 'libs/Lipsum.php';

/*
define WP_CRAWLER_METHOD as
    file_get_html
    _viewSource
*/
define( 'WP_CRAWLER_METHOD', '_viewSource' );

/*-----------------------------------------------------------------------------------*/
/*  _viewSource
/*-----------------------------------------------------------------------------------*/
function _viewSource($url) {
    $parse_url = parse_url($url);
    if(!isset($parse_url['host'])) return null;
    
    $headers = array("Host: {$parse_url['host']}");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    // curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/5.0 (Linux; U; Android 4.0.3; ko-kr; LG-L160L Build/IML74K) AppleWebkit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30");
    curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.80 Safari/537.36");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
    curl_setopt($ch, CURLOPT_REFERER, $url);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    return curl_exec($ch);
    curl_close($ch);
}

/*-----------------------------------------------------------------------------------*/
/*	Sources Post Type
/*-----------------------------------------------------------------------------------*/

add_action( 'init', 'wp_crawler_post_types_init' );
function wp_crawler_post_types_init() {
    $labels = array(
        'name'               => _x( 'Sources', 'post type general name', 'wp-crawler' ),
        'singular_name'      => _x( 'Source', 'post type singular name', 'wp-crawler' ),
        'menu_name'          => _x( 'WP Crawler', 'admin menu', 'wp-crawler' ),
        'name_admin_bar'     => _x( 'Source', 'add new on admin bar', 'wp-crawler' ),
        'add_new'            => _x( 'Add New', 'source', 'wp-crawler' ),
        'add_new_item'       => __( 'Add New Source', 'wp-crawler' ),
        'new_item'           => __( 'New Source', 'wp-crawler' ),
        'edit_item'          => __( 'Edit Source', 'wp-crawler' ),
        'view_item'          => __( 'View Source', 'wp-crawler' ),
        'all_items'          => __( 'All Sources', 'wp-crawler' ),
        'search_items'       => __( 'Search Sources', 'wp-crawler' ),
        'parent_item_colon'  => __( 'Parent Sources:', 'wp-crawler' ),
        'not_found'          => __( 'No sources found.', 'wp-crawler' ),
        'not_found_in_trash' => __( 'No sources found in Trash.', 'wp-crawler' )
    );

    $args = array(
        'labels'             => $labels,
        'description'        => __( 'Description.', 'wp-crawler' ),
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => false,
        'rewrite'            => array( 'slug' => 'source' ),
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title' )
    );

    register_post_type( 'wp-crawler-source', $args );

    $labels = array(
        'name'               => _x( 'WP Crawler Posts', 'post type general name', 'wp-crawler' ),
        'singular_name'      => _x( 'WP Crawler Post', 'post type singular name', 'wp-crawler' ),
        'menu_name'          => _x( 'WP Crawler Posts', 'admin menu', 'wp-crawler' ),
        'name_admin_bar'     => _x( 'WP Crawler Post', 'add new on admin bar', 'wp-crawler' ),
        'all_items'          => __( 'All', 'wp-crawler' ),
        'search_items'       => __( 'Search', 'wp-crawler' ),
        'parent_item_colon'  => __( 'Parent:', 'wp-crawler' ),
        'not_found'          => __( 'No posts found.', 'wp-crawler' ),
        'not_found_in_trash' => __( 'No posts found in Trash.', 'wp-crawler' )
    );

    $args = array(
        'labels'             => $labels,
        'description'        => __( 'Description.', 'Crawled Post' ),
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => false,
        'rewrite'            => array( 'slug' => 'crawled-post' ),
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array(
            'title',
            'editor',
            'author',
            'excerpt',
            'thumbnail'
        ),
        'taxonomies' => array('category', 'post_tag')
    );

    register_post_type( 'wp_crawler_post', $args );
}

function wp_crawler_get_sub_categories($parent = 0, $depth = 0) {
    $categories = get_categories(array('parent' => $parent, 'hide_empty' => 0));
    if (empty($categories)) {
        return $categories;
    }
    $pad = str_repeat('&nbsp;', $depth * 4);
    $result = array();
    foreach ($categories as $category) {
        $result[$category->term_id] = $pad . $category->name;
        foreach (wp_crawler_get_sub_categories($category->term_id, $depth + 1) as $subcatId => $subcatName) {
            $result[$subcatId] = $subcatName;
        }
    }
    return $result;
}

/*-----------------------------------------------------------------------------------*/
/*	Sources Metabox
/*-----------------------------------------------------------------------------------*/
add_action( 'cmb2_admin_init', 'wp_crawler_metabox_init' );
function wp_crawler_metabox_init() {
    $prefix = '_wp_crawler_source_';

    $cmb = new_cmb2_box( array(
        'id'            => 'source_default_metabox',
        'title'         => __( 'Settings', 'wp-crawler' ),
        'object_types'  => array( 'wp-crawler-source' ),
        'context'       => 'normal',
        'priority'      => 'high',
        'cmb_styles'    => true,
        'show_names'    => true,
    ) );

    $cmb->add_field( array(
        'name' => __( 'URL', 'wp-crawler' ),
        'id'   => $prefix . 'url',
        'type' => 'text',
    ) );
    $cmb->add_field(array(
        'name' => __( 'Type', 'wp-crawler' ),
        'id' => $prefix . 'type',
        'type' => 'select',
        'options' => array(
            'single' => __( 'Single Item', 'wp-crawler' ),
            'listing' => __( 'Multiple Items', 'wp-crawler' )
        )
    ));
    $cmb->add_field(array(
        'name' => __( 'Post Status', 'wp-crawler' ),
        'id' => $prefix . 'post_status',
        'type' => 'select',
        'options' => array(
            'draft' => __( 'Draft', 'wp-crawler' ),
            'publish' => __( 'Publish', 'wp-crawler' ),
            'pending' => __( 'Pending', 'wp-crawler' ),
            'future' => __( 'Future', 'wp-crawler' ),
            'private' => __( 'Private', 'wp-crawler' ),
        )
    ));

    $categories = wp_crawler_get_sub_categories(0);
    $cmb->add_field( array(
        'name' => __( 'Post Category', 'wp-crawler' ),
        'id'   => $prefix . 'category',
        'type' => 'select',
        'options' => $categories
    ) );
    $users = array();
    foreach (get_users() as $user) {
        $users[$user->ID] = esc_html($user->user_nicename);
    }
    $cmb->add_field(array(
        'name' => __( 'Post Author', 'wp-crawler' ),
        'id' => $prefix . 'post_author',
        'type' => 'select',
        'options' => $users
    ));


    $cmb = new_cmb2_box( array(
        'id'            => 'source_listing_metabox',
        'title'         => __( 'Listing Page', 'wp-crawler' ),
        'object_types'  => array( 'wp-crawler-source' ),
        'context'       => 'normal',
        'priority'      => 'high',
        'cmb_styles'    => true,
        'show_names'    => true,
    ) );

    $cmb->add_field( array(
        'name' => 'Item',
        'type' => 'title',
        'id' => 'item',
    ) );

    $cmb->add_field( array(
        'name' => __( 'Selector', 'wp-crawler' ),
        'id'   => $prefix . 'item',
        'type' => 'text',
    ) );

    $cmb->add_field( array(
        'name' => __( 'Attribute', 'wp-crawler' ),
        'id'   => $prefix . 'item_attr',
        'type' => 'text',
        'default' => 'href'
    ) );

    $cmb = new_cmb2_box( array(
        'id'            => 'source_metabox',
        'title'         => __( 'Single Item', 'wp-crawler' ),
        'object_types'  => array( 'wp-crawler-source' ),
        'context'       => 'normal',
        'priority'      => 'high',
        'cmb_styles'    => true,
        'show_names'    => true,
    ) );

    $cmb->add_field( array(
        'name' => 'Post Title',
        'type' => 'title',
        'id' => 'title',
    ) );

    $cmb->add_field( array(
        'name' => __( 'Selector', 'wp-crawler' ),
        'id'   => $prefix . 'title',
        'type' => 'text',
    ) );

    $cmb->add_field( array(
        'name' => __( 'Attribute', 'wp-crawler' ),
        'id'   => $prefix . 'title_attr',
        'type' => 'text',
        'default' => 'innertext'
    ) );

    $cmb->add_field( array(
        'name' => 'Post Content',
        'type' => 'title',
        'id' => 'content',
    ) );

    $cmb->add_field( array(
        'name' => __( 'Selector', 'wp-crawler' ),
        'id'   => $prefix . 'content',
        'type' => 'text',
    ) );

    $cmb->add_field( array(
        'name' => __( 'Attribute', 'wp-crawler' ),
        'id'   => $prefix . 'content_attr',
        'type' => 'text',
        'default' => 'innertext'
    ) );

    $cmb->add_field( array(
        'name' => 'Post Excerpt',
        'type' => 'title',
        'id' => 'excerpt',
    ) );

    $cmb->add_field( array(
        'name' => __( 'Selector', 'wp-crawler' ),
        'id'   => $prefix . 'excerpt',
        'type' => 'text',
    ) );

    $cmb->add_field( array(
        'name' => __( 'Attribute', 'wp-crawler' ),
        'id'   => $prefix . 'excerpt_attr',
        'type' => 'text',
        'default' => 'innertext'
    ) );

    $cmb->add_field( array(
        'name' => 'Post Date',
        'type' => 'title',
        'id' => 'date',
    ) );

    $cmb->add_field( array(
        'name' => __( 'Selector', 'wp-crawler' ),
        'id'   => $prefix . 'date',
        'type' => 'text',
    ) );

    $cmb->add_field( array(
        'name' => __( 'Attribute', 'wp-crawler' ),
        'id'   => $prefix . 'date_attr',
        'type' => 'text',
        'default' => 'innertext'
    ) );

    $cmb->add_field( array(
        'name' => 'Post Thumbnail (Image URL)',
        'type' => 'title',
        'id' => 'thumbnail',
    ) );

    $cmb->add_field( array(
        'name' => __( 'Selector', 'wp-crawler' ),
        'id'   => $prefix . 'thumbnail',
        'type' => 'text',
    ) );

    $cmb->add_field( array(
        'name' => __( 'Attribute', 'wp-crawler' ),
        'id'   => $prefix . 'thumbnail_attr',
        'type' => 'text',
        'default' => 'src'
    ) );

    $cmb->add_field( array(
        'name' => 'Content Removal',
        'type' => 'title',
        'id' => 'removal',
    ) );

    $cmb->add_field( array(
        'name' => 'Selector',
        'type' => 'text',
        'id' => $prefix . 'removal',
    ) );

    $taxonomies = $cmb->add_field( array(
        'id'   => $prefix . 'taxonomies',
        'type' => 'group',
        'options'     => array(
            'group_title'   => __( 'Taxonomy {#}', 'wp-crawler' ),
            'add_button'    => __( 'Add Another Taxonomy', 'wp-crawler' ),
            'remove_button' => __( 'Remove Taxonomy', 'wp-crawler' ),
            'sortable' => true,
            'closed' => true,
        ),
    ) );

    $cmb->add_group_field( $taxonomies, array(
        'name' => __( 'Taxonomy Slug', 'wp-crawler' ),
        'id'   => $prefix . 'taxonomy_slug',
        'type' => 'text'
    ) );

    $cmb->add_group_field( $taxonomies, array(
        'name' => __( 'Taxonomy Selector', 'wp-crawler' ),
        'id'   => $prefix . 'taxonomy_selector',
        'type' => 'text'
    ) );

    $cmb->add_group_field( $taxonomies, array(
        'name' => __( 'Taxonomy Attribute', 'wp-crawler' ),
        'id'   => $prefix . 'taxonomy_attr',
        'type' => 'text'
    ) );

    $cmb->add_group_field( $taxonomies, array(
        'name' => __( 'Multiple Value?', 'wp-crawler' ),
        'id' => $prefix . 'taxonomy_multiple',
        'type' => 'select',
        'show_option_none' => false,
        'default' => 'no',
        'options' => array(
            'no' => __( 'No', 'wp-crawler' ),
            'yes' => __( 'Yes', 'wp-crawler' ),
        ),
    ) );

    $custom_fields = $cmb->add_field( array(
        'id'   => $prefix . 'custom_fields',
        'type' => 'group',
        'options'     => array(
            'group_title'   => __( 'Custom Field {#}', 'wp-crawler' ),
            'add_button'    => __( 'Add Another Custom Field', 'wp-crawler' ),
            'remove_button' => __( 'Remove Custom Field', 'wp-crawler' ),
            'sortable' => true,
            'closed' => true,
        ),
    ) );

            $cmb->add_group_field( $custom_fields, array(
                'name' => __( 'Custom Field Name', 'wp-crawler' ),
                'id'   => $prefix . 'custom_field_slug',
                'type' => 'text'
            ) );

            $cmb->add_group_field( $custom_fields, array(
                'name' => __( 'Custom Field Selector', 'wp-crawler' ),
                'id'   => $prefix . 'custom_field_selector',
                'type' => 'text'
            ) );

            $cmb->add_group_field( $custom_fields, array(
                'name' => __( 'Custom Field Attribute', 'wp-crawler' ),
                'id'   => $prefix . 'custom_field_attr',
                'type' => 'text',
            ) );
}

/*-----------------------------------------------------------------------------------*/
/*	Custom Admin Script & Style
/*-----------------------------------------------------------------------------------*/
    add_action( 'admin_enqueue_scripts', 'wp_crawler_admin_scripts' );
function wp_crawler_admin_scripts() {
    wp_enqueue_style( 'wp-crawler-style', WP_CRAWLER_URL . 'assets/css/admin-style.css' );
}

/*-----------------------------------------------------------------------------------*/
/*	Add Admin Menu
/*-----------------------------------------------------------------------------------*/
    function wp_crawler_admin_menu() {
        add_submenu_page( 'edit.php?post_type=wp-crawler-source', __( 'Run', 'wp-crawler'), __('Run', 'wp-crawler'), 'manage_options', 'wp-crawler', 'wp_crawler_run' );
    }
add_action( 'admin_menu', 'wp_crawler_admin_menu' );

/*-----------------------------------------------------------------------------------*/
/*	Settings Page
/*-----------------------------------------------------------------------------------*/
    function wp_crawler_run() { ?>
    <div class="wrap">
        <h1>Run Crawler</h1>
<?php
    if (  ! empty( $_POST['crawler_source'] )  ) {
        $crawler_source = $_POST['crawler_source'];
        $crawler_url = $_POST['crawler_url'];
        if (empty($crawler_url)) {
            $crawler_url = get_post_meta( $crawler_source, '_wp_crawler_source_url', true);
        }
        $crawler_args = array(
            'post_status' => $_POST['crawler_post_status'],
            'post_author' => $_POST['crawler_post_author'],
            'post_type' => $_POST['crawler_post_type'],
            'post_cat' => $_POST['cat'] != 1 ? $_POST['cat'] : '',
            'crawler_type' => $_POST['crawler_type']
        );

        $default_args = array(
            'post_status' => get_post_meta( $crawler_source, '_wp_crawler_source_post_status', true),
            'post_author' => get_post_meta( $crawler_source, '_wp_crawler_source_post_author', true),
            'post_type' => 'wp_crawler_post',
            'post_cat' => get_post_meta( $crawler_source, '_wp_crawler_source_category', true),
            'crawler_type' => get_post_meta( $crawler_source, '_wp_crawler_source_type', true),
        );
        $crawler_args = wp_parse_args(array_filter($crawler_args), $default_args);

        if ( 'listing' === $crawler_args['crawler_type'] ) {
            $source_item_selector = get_post_meta( $crawler_source, '_wp_crawler_source_item', true );
            $source_item_attr = get_post_meta( $crawler_source, '_wp_crawler_source_item_attr', true );
            if ( empty( $source_item_attr ) ) {
                $source_item_attr = 'href';
            }
            if ( ! empty( $source_item_selector ) ) {
                if( WP_CRAWLER_METHOD == 'file_get_html' ) {
                    $context_options = array(
                        'http' => array(
                            'method' => "GET",
                            'header' => "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.80 Safari/537.36"
                        )
                    );
                    $context = stream_context_create($context_options);
                    $html = file_get_html( $crawler_url, false, $context );
                }
                else if( WP_CRAWLER_METHOD == '_viewSource' ) {
                    $html = str_get_html( _viewSource( $crawler_url ) );
                }

                if ( $html ) {
                    $crawler_urls = $html->find( $source_item_selector );
                    if ( empty ( $crawler_urls ) ) {
                        echo '<div class="updated notice notice-error below-h2"><p>Can\'t Get Single Item\'s URLs from Selector: <code>' . $source_item_selector . '</code>. Please <a href="' . get_home_url() . '/wp-admin/post.php?post=' . $crawler_source . '&action=edit">Update Source</a>.</p></div>';
                    } else {
                        foreach( $crawler_urls as $crawler_single_url ) {
                            $single_url = parse_url( $crawler_single_url->$source_item_attr );
                            if ( empty( $single_url['host'] ) ) {
                                $parse_crawler_url = parse_url( $crawler_url );
                                $item_single_url = $parse_crawler_url['scheme'] . '://' . $parse_crawler_url['host'] . $crawler_single_url->$source_item_attr;
                            } else if ( empty( $single_url['scheme'] ) ) {
                                $item_single_url = 'http://' . ltrim( $crawler_single_url->$source_item_attr, '/' );
                            } else {
                                $item_single_url = $crawler_single_url->$source_item_attr;
                            }
                            wp_crawler_insert_post( $item_single_url, $crawler_source, $crawler_args );
                        }
                    }
                } else {
                    echo '<div class="updated notice notice-error below-h2"><p>Can\'t Read This Site</p></div>';
                }
            } else { ?>
                <div class="updated notice notice-error below-h2">
                    <p><?php _e( 'Missing Selector for Item in Listing Page.', 'wp-crawler' ); ?></p>
                </div>
<?php }
        } else {
            wp_crawler_insert_post( $crawler_url, $crawler_source, $crawler_args );
        }
    } else { ?>
        <?php if ( ! empty( $_POST['crawler_source'] ) ) : ?>
            <div class="updated notice notice-error below-h2">
                <p><?php _e( 'Please Input Source URL.', 'wp-crawler' ); ?></p>
            </div>
        <?php endif; ?>
<?php
        $the_query = new WP_Query( array( 'post_type' => 'wp-crawler-source', 'posts_per_page' => -1 ) );
if ( $the_query->have_posts() ) :
?>
        <form action="<?php __FILE__ ?>" method="post">
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="crawler_source">Source</label>
                    </th>
                    <td>
                        <select name="crawler_source" id="crawler_source" class="postform">
                            <?php  $i = 0; while ( $the_query->have_posts() ) : $the_query->the_post(); ?>
                            <option value="<?php echo get_the_ID(); ?>"<?php echo ( 0 === $i ) ? ' selected="selected"' : ''; ?>><?php the_title(); ?></option>
                            <?php $i++; endwhile; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="crawler_type">Type</label>
                    </th>
                    <td>
                        <select name="crawler_type" id="crawler_type" class="postform">
                            <option value="">Default</option>
                            <option value="single">Single Item</option>
                            <option value="listing">Multiple Items</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="crawler_url">URL</label></th>
                    <td><input name="crawler_url" id="crawler_url" type="text" class="regular-text code"></td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="cat">Post Category</label>
                    </th>
                    <td>
                        <?php wp_dropdown_categories( 'hide_empty=0' ); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="crawler_post_status">Post Status</label>
                    </th>
                    <td>
                        <select name="crawler_post_status" id="crawler_post_status" class="postform">
                            <option value="" selected="selected">Default</option>
                            <option value="draft">Draft</option>
                            <option value="publish">Publish</option>
                            <option value="pending">Pending</option>
                            <option value="future">Future</option>
                            <option value="private">Private</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="crawler_post_author">Post Author</label>
                    </th>
                    <td><input name="crawler_post_author" id="crawler_post_author" type="text" class="regular-text code" placeholder="1"></td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="crawler_post_type">Post Type</label>
                    </th>
                    <td><input name="crawler_post_type" id="crawler_post_type" type="text" class="regular-text code" placeholder="post"></td>
                </tr>
                <tr>
                    <td></td>
                    <td><input type="submit" name="submit" value="Run Crawler" class="button-primary" /></td>
                </tr>
            </tbody>
        </table>
        </form>
        <?php else : ?>
            <div class="updated notice notice-error below-h2">
                <p>Please <a href="post-new.php?post_type=wp-crawler-source">Add Source</a> Before Run.</p>
            </div>
        <?php endif; ?>
        <?php wp_reset_postdata(); ?>
        <?php } ?>
    </div>
<?php }

/*-----------------------------------------------------------------------------------*/
/*	Insert Featured Image
/*-----------------------------------------------------------------------------------*/
function wp_crawler_set_featured_image( $post_id, $image_url, $item_url ) {
    $attach_id = wp_crawler_get_image($post_id, $image_url, $item_url);
    update_post_meta( $post_id,'_thumbnail_id', $attach_id );
}

function wp_crawler_get_image($post_id, $image_url, $item_url) {
    $parsed_item_url = parse_url( $item_url );
    $parsed_image_url = parse_url( $image_url );
    if ( empty( $parsed_image_url['host'] ) ) {
        $image_url = $parsed_item_url['scheme'] . '://' . $parsed_item_url['host'] . $image_url;
    }

    $attach_id = wp_crawler_check_exists($image_url);
    if ($attach_id) {
        return $attach_id;
    }

    $base_file_name = basename($image_url);

    $upload_folder = ABSPATH . "wp-content/uploads/" . date('Y/m');
    $filename = $upload_folder . '/' . $post_id . '-' . $base_file_name;
    if ( false === file_exists( $upload_folder ) ) {
        chmod( ABSPATH . 'wp-content/uploads/', 0755 );
        mkdir( $upload_folder );
        chmod( $upload_folder, 0755 );
    }
    copy( $image_url, $filename );
    $wp_filetype = wp_check_filetype( basename( $filename ), null );
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => $base_file_name,
        'post_content' => '',
        'post_status' => 'inherit'
    );
    $attach_id = wp_insert_attachment( $attachment, $filename, $post_id );

    require_once( ABSPATH . "wp-admin" . '/includes/image.php' );
    $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
    wp_update_attachment_metadata( $attach_id,  $attach_data );
    update_post_meta( $attach_id, '_original_url', $image_url );
    clean_post_cache($attach_id);
    return $attach_id;
}

/*-----------------------------------------------------------------------------------*/
/*	Insert Post
/*-----------------------------------------------------------------------------------*/
    function wp_crawler_insert_post( $url, $source, $args ) {
        if ( ! empty( $url ) && ! empty( $source ) ) {
            if( WP_CRAWLER_METHOD == 'file_get_html' ) {
                $context_options = array(
                    'http' => array(
                        'method' => "GET",
                        'header' => "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.80 Safari/537.36"
                    )
                );
                $context = stream_context_create($context_options);
                $html = file_get_html( $url, false, $context );
            }
            else if( WP_CRAWLER_METHOD == '_viewSource' ) {
                $html = str_get_html(_viewSource($url));
            }

            $title_selector = get_post_meta( $source, '_wp_crawler_source_title', true );
            $title_attr = get_post_meta( $source, '_wp_crawler_source_title_attr', true );
            if ( empty( $title_attr ) ) $title_attr = 'innertext';
            if ( $title_selector ) {
                $title = trim( $html->find( $title_selector, 0 )->$title_attr );
            } else {
                $title = '';
            }

            $content_selector = get_post_meta( $source, '_wp_crawler_source_content', true );
            $content_attr = get_post_meta( $source, '_wp_crawler_source_content_attr', true );
            if ( empty( $content_attr ) ) $content_attr = 'innertext';
            if ( $content_selector ) {
                $content = trim( $html->find( $content_selector, 0 )->$content_attr );
            } else {
                $content = '';
            }
            $contentHtml = str_get_html($content);

            $removal_selector = get_post_meta( $source, '_wp_crawler_source_removal', true );
            $removal_selectors = array_filter(array_map('trim', explode(',', $removal_selector)));
            foreach ($removal_selectors as $selector) {
                foreach ($contentHtml->find($selector) as $node) {
                    if (strpos($selector, '[') !== false) {
                        $attr = str_replace('[', '', str_replace(']', '', $selector));
                        $node->$attr = '';
                        $a = 'b';
                    } else {
                        $node->outertext = '';
                    }
                }
            }
            $content = (string) $contentHtml;
            $contentHtml = str_get_html($content);


            $excerpt_selector = get_post_meta( $source, '_wp_crawler_source_excerpt', true );
            $excerpt_attr = get_post_meta( $source, '_wp_crawler_source_excerpt_attr', true );
            if ( empty( $excerpt_attr ) ) $excerpt_attr = 'innertext';
            if ( $excerpt_selector ) {
                $excerpt = trim( $html->find( $excerpt_selector, 0 )->$excerpt_attr );
            } else {
                $excerpt = '';
            }

            $date_selector = get_post_meta( $source, '_wp_crawler_source_date', true );
            $date_attr = get_post_meta( $source, '_wp_crawler_source_date_attr', true );
            if ( empty( $date_attr ) ) $date_attr = 'innertext';
            if ( $date_selector ) {
                $date = date( 'Y-m-d H:i:s', strtotime( $html->find( $date_selector, 0 )->$date_attr ) );
            } else {
                $date = '';
            }

            $post_id = wp_crawler_check_exists( $url );

            if ( $args['post_status'] && in_array( $args['post_status'], array( 'draft', 'publish', 'pending', 'future', 'private' ) ) ) {
                $status = $args['post_status'];
            } else {
                $status = 'draft';
            }

            if ( $args['post_author'] && ( false !== get_user_by( 'id', $args['post_author'] ) ) ) {
                $author_id = $args['post_author'];
            } else {
                $author_id = 1;
            }

            if ( $args['post_type'] && post_type_exists(  $args['post_type'] ) ) {
                $post_type = $args['post_type'];
            } else {
                $post_type = 'post';
            }

            if ( $args['post_cat'] && $args['post_cat'] != 1 ) {
                $post_category = array( $args['post_cat'] );
            } else {
                $post_category = '';
            }

            // Insert Post
            $post_data = array(
                'ID'        => $post_id,
                'post_title'     => $title,
                'post_content'   => $content,
                'post_excerpt'   => $excerpt,
                'post_date'      => $date,
                'post_status'    => $status,
                'post_author'    => $author_id,
                'post_type'      => $post_type,
                'comment_status' => 'open',
                'post_category'  => $post_category,
            );

            $post_id = wp_insert_post( $post_data );

            if ( is_wp_error( $post_id ) ) {
                echo '<div class="updated notice notice-error below-h2"><p>' . $return->get_error_message() . '</p></div>';
            } else {

                // Insert Thumbnail
                $thumbnail_selector = get_post_meta( $source, '_wp_crawler_source_thumbnail', true );
                $thumbnail_attr = get_post_meta( $source, '_wp_crawler_source_thumbnail_attr', true );
                if ( empty( $thumbnail_attr ) ) $thumbnail_attr = 'src';
                if ( $thumbnail_selector ) {
                    wp_crawler_set_featured_image( $post_id, $html->find( $thumbnail_selector, 0 )->$thumbnail_attr, $url );
                }

                foreach ($contentHtml->find('img') as $img) {
                    $attach_id = wp_crawler_get_image($post_id, $img->src, $url);
                    wp_cache_delete($attach_id, 'post_meta');
                    $img->src = wp_get_attachment_url($attach_id);
                }

                $post_data = array(
                    'ID' => $post_id,
                    'post_content' => (string) $contentHtml
                );
                wp_update_post($post_data);

                // Insert Taxonomies
                $taxonomies = get_post_meta( $source, '_wp_crawler_source_taxonomies', true );
                if ( $taxonomies ) {
                    foreach ( $taxonomies as $taxonomy ) {
                        if ( empty( $taxonomy['_wp_crawler_source_taxonomy_attr'] ) ) {
                            $taxonomy_attr = 'innertext';
                        } else {
                            $taxonomy_attr = $taxonomy['_wp_crawler_source_taxonomy_attr'];
                        }
                        if ( 'yes' === $taxonomy['_wp_crawler_source_taxonomy_multiple'] ) {
                            $taxonomy_array = '';
                            foreach( $html->find( $taxonomy['_wp_crawler_source_taxonomy_selector'] ) as $taxonomy_name ) {
                                $taxonomy_array[] = $taxonomy_name->$taxonomy_attr;
                            }
                            if ( $taxonomy_array && ! empty( $taxonomy['_wp_crawler_source_taxonomy_slug'] ) ) {
                                wp_set_object_terms( $post_id, $taxonomy_array, $taxonomy['_wp_crawler_source_taxonomy_slug'] );
                            }
                        } else {
                            if ( ! empty ( $taxonomy['_wp_crawler_source_taxonomy_slug'] ) ) {
                                wp_set_object_terms( $post_id, array( trim( $html->find( $custom_field['_wp_crawler_source_taxonomy_selector'], 0 )->$taxonomy_attr ) ), $taxonomy['_wp_crawler_source_taxonomy_slug'] );
                            }
                        }
                    }
                }

                // Insert Custom Fields
                $custom_fields = get_post_meta( $source, '_wp_crawler_source_custom_fields', true );
                if ( $custom_fields ) {
                    foreach ( $custom_fields as $custom_field ) {
                        if ( empty( $custom_field['_wp_crawler_source_custom_field_attr'] ) ) {
                            $custom_field_attr = 'innertext';
                        } else {
                            $custom_field_attr = $custom_field['_wp_crawler_source_custom_field_attr'];
                        }
                        update_post_meta( $post_id, $custom_field['_wp_crawler_source_custom_field_slug'], maybe_unserialize( trim( $html->find( $custom_field['_wp_crawler_source_custom_field_selector'], 0 )->$custom_field_attr ) ) );
                    }
                }
                update_post_meta( $post_id, '_original_url', $url );
                echo '<div class="updated notice notice-success below-h2"><p>Well Done!</p></div>';
            }
        } else {
            echo '<div class="updated notice notice-success below-h2"><p>Missing Source &amp; URL</p></div>';
        }
    }

/*-----------------------------------------------------------------------------------*/
/*	Check Post Exists
/*-----------------------------------------------------------------------------------*/
function wp_crawler_check_exists( $url ) {
    global $wpdb;
    $url_in_db = $wpdb->get_row("SELECT * FROM $wpdb->postmeta WHERE `meta_value` = '$url' AND `meta_key` = '_original_url'");
    if ( $url_in_db ) {
        return $url_in_db->post_id;
    } else {
        return false;
    }
}

add_action('manage_wp_crawler_post_posts_custom_column', 'wp_crawler_manage_excerpt_col', 10, 2);
add_filter('manage_wp_crawler_post_posts_columns', 'wp_crawler_add_excerpt_col');

function wp_crawler_manage_excerpt_col($column, $post_id) {
    if ($column == 'wp_crawler_post_excerpt') {
        $post = get_post($post_id);

        echo '<div>';
            echo $post->post_excerpt;
        echo '</div>';
    }
}

function wp_crawler_add_excerpt_col($columns) {
    wp_crawler_array_insert( $columns, 'author', array('wp_crawler_post_excerpt' => __('Excerpt') ));
    return $columns;
}

function wp_crawler_array_insert(&$array, $position, $insert)
{
    if (is_int($position)) {
        array_splice($array, $position, 0, $insert);
    } else {
        $pos   = array_search($position, array_keys($array));
        $array = array_merge(
            array_slice($array, 0, $pos),
            $insert,
            array_slice($array, $pos)
        );
    }
}

function wp_crawler_post_published_notification( $ID, $post ) {
    if ($post->post_type == 'wp_crawler_post') {
        set_post_type($ID, 'post');
    }
}
add_action( 'publish_wp_crawler_post', 'wp_crawler_post_published_notification', 10, 2 );

add_action( 'admin_init' , 'wp_crawler_set_default_filters', 1 );

function wp_crawler_set_default_filters() {
    if (
        is_admin()
        && isset($_GET['post_type'])
        && $_GET['post_type'] == 'wp_crawler_post'
    ) {
        if (empty($_GET['date_from']) && empty($_GET['date_to'])) {
            $_GET['date_from'] = $_GET['date_to'] = date('Y/m/d');
            $_GET['date_predefined'] = 'today';
        }
        if (!isset($_GET['author'])) {
            $_GET['author'] = get_current_user_id();
        }
    }
}

require_once dirname(__FILE__) . '/wp-crawler-cron.php';
