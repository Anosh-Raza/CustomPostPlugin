<?php
/**
 * Plugin Name: Custom Post Text
 * Description: Add and manage custom text after post titles.
 * Version: 1.0
 * Author: Your Name
 */

// Activation hook
register_activation_hook(__FILE__, 'custom_post_text_activate');

function custom_post_text_activate() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'custom_post_text';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_id mediumint(9) NOT NULL,
        custom_text text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'custom_post_text_deactivate');

function custom_post_text_deactivate() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'custom_post_text';

    $sql = "DROP TABLE IF EXISTS $table_name;";

    $wpdb->query($sql);
}

// Save custom text when post is saved
function save_custom_text($post_id) {
    if (isset($_POST['custom_text'])) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'custom_post_text';

        $post_id = intval($post_id);
        $custom_text = sanitize_text_field($_POST['custom_text']);

        $wpdb->replace(
            $table_name,
            array(
                'post_id' => $post_id,
                'custom_text' => $custom_text,
            ),
            array('%d', '%s')
        );
    }
}

add_action('save_post', 'save_custom_text');

// Display custom text after the post title
function display_custom_text_after_title($title) {
    // Check if it's a single post
    if (is_single() && get_post_type() === 'post') {
        global $wpdb;

        $table_name = $wpdb->prefix . 'custom_post_text';

        $custom_text = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT custom_text FROM $table_name WHERE post_id = %d",
                get_the_ID()
            )
        );

        if ($custom_text) {
            $title .= '<p>' . esc_html($custom_text) . '</p>';
        }
    }

    return $title;
}

add_filter('the_title', 'display_custom_text_after_title');

// Add a table to display and manage custom text for each post
function add_custom_text_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'custom_post_text';

    $posts = $wpdb->get_results("SELECT * FROM $table_name");

    echo '<h2>Custom Text Management</h2>';
    echo '<table class="widefat">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Post ID</th>';
    echo '<th>Post Title</th>';
    echo '<th>Custom Text</th>';
    echo '<th>Action</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($posts as $post) {
        $post_id = $post->post_id;
        $post_title = get_the_title($post_id);
        $custom_text = $post->custom_text;

        echo '<tr>';
        echo "<td>$post_id</td>";
        echo "<td>$post_title</td>";
        echo "<td>$custom_text</td>";
        echo '<td>';
        echo '<a href="' . admin_url("admin.php?page=custom-post-text-settings&post_id=$post_id&action=edit") . '">Edit</a> | ';
        echo '<a href="#" class="delete-custom-text" data-post-id="' . $post_id . '">Delete</a>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';

    ?>
    <script>
        jQuery(document).ready(function($) {
            $('.delete-custom-text').on('click', function(e) {
                e.preventDefault();
                var postID = $(this).data('post-id');
                if (confirm('Are you sure you want to delete the custom text?')) {
                    window.location.href = '<?php echo admin_url("admin.php?page=custom-post-text-settings&action=delete&post_id="); ?>' + postID;
                }
            });
        });
    </script>
    <?php
}

// Add settings menu to the admin dashboard
function custom_post_text_menu() {
    add_menu_page(
        'Custom Post Text Settings',
        'Custom Post Text',
        'manage_options',
        'custom-post-text-settings',
        'custom_post_text_settings_page'
    );
}

add_action('admin_menu', 'custom_post_text_menu');

// Display settings page in the admin dashboard
function custom_post_text_settings_page() {
    ?>
    <div class="wrap">
        <h1>Custom Post Text Settings</h1>
        
        <?php
        if (isset($_GET['post_id'])) {
            $post_id = intval($_GET['post_id']);
            $post_title = get_the_title($post_id);
            $custom_text = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT custom_text FROM $table_name WHERE post_id = %d",
                    $post_id
                )
            );
            ?>
            <h2>Edit Custom Text for : <strong><?php echo esc_html($post_title); ?></strong></h2>
            <form method="post" action="">
                <label for="custom_text">Custom Text:</label>
                <input type="text" name="custom_text" id="custom_text" value="<?php echo esc_attr($custom_text); ?>" style="width: 100%;">
                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                <input type="submit" name="save_custom_text" class="button-primary" value="Save Custom Text">
            </form>
            <?php
        } else {
            // Display the custom text table
            add_custom_text_table();
        }
        ?>
    </div>
    <?php
}

// Save custom text when edited or deleted from the settings page
function save_custom_text_from_settings() {
    if (isset($_POST['save_custom_text'])) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'custom_post_text';

        $post_id = intval($_POST['post_id']);
        $custom_text = sanitize_text_field($_POST['custom_text']);

        $wpdb->replace(
            $table_name,
            array(
                'post_id' => $post_id,
                'custom_text' => $custom_text,
            ),
            array('%d', '%s')
        );
    } elseif (isset($_GET['post_id']) && isset($_GET['action']) && $_GET['action'] === 'delete') {
        global $wpdb;

        $table_name = $wpdb->prefix . 'custom_post_text';

        $post_id = intval($_GET['post_id']);

        $wpdb->delete(
            $table_name,
            array('post_id' => $post_id),
            array('%d')
        );
    }
}

add_action('admin_init', 'save_custom_text_from_settings');
