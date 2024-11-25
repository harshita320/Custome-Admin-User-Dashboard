<?php
/**
 * Plugin Name: Custom user and admin dashboard 
 * Description: A plugin to provide custom login and registration dashboard .
 * Version: 1.0
 * Author: hph
 */

if (!defined('ABSPATH')) {
    exit; 
}


function clr_enqueue_assets() {
    wp_enqueue_style('clr-styles', plugin_dir_url(__FILE__) . 'style.css');
}
add_action('wp_enqueue_scripts', 'clr_enqueue_assets');


add_shortcode('clr_register', 'clr_register_form');
add_shortcode('clr_login', 'clr_login_form');

function clr_register_form() {
    if (is_user_logged_in()) {
        return '<p>You are already logged in.</p>';
    }

    ob_start();
    ?>
    <form id="clr-register-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST">
        <h2>Register</h2>
        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <label for="role">Role:</label>
        <select name="role" required>
            <option value="subscriber">User</option>
            <option value="author">Author</option>
            <option value="editor">Editor</option>
            <option value="administrator">Admin</option>
        </select>
        <input type="hidden" name="action" value="clr_register_user">
        <button type="submit">Register</button>
    </form>
    <?php
    return ob_get_clean();
}
// Handle User Registration with Role
function clr_handle_register() {
    if (!isset($_POST['username'], $_POST['email'], $_POST['password'], $_POST['role'])) {
        wp_die('Invalid registration request.');
    }

    $username = sanitize_text_field($_POST['username']);
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];
    $role = sanitize_text_field($_POST['role']);

    // Validate role
    $valid_roles = ['subscriber', 'author', 'editor', 'administrator'];
    if (!in_array($role, $valid_roles)) {
        wp_die('Invalid role selected.');
    }

    // Create user with the specified role
    $user_id = wp_create_user($username, $password, $email);
    if (is_wp_error($user_id)) {
        wp_die($user_id->get_error_message());
    }

    // Update role
    $user = new WP_User($user_id);
    $user->set_role($role);

    wp_redirect(home_url('/login-register')); 
    exit;
}
add_action('admin_post_nopriv_clr_register_user', 'clr_handle_register');


function clr_login_form() {
    if (is_user_logged_in()) {
        return '<p>You are already logged in.</p>';
    }

    ob_start();
    ?>
    <form id="clr-login-form" action="<?php echo esc_url(wp_login_url(home_url('/user-dashboard'))); ?>" method="POST">
        <h2>Login</h2>
        <input type="text" name="log" placeholder="Username or Email" required>
        <input type="password" name="pwd" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
    <?php
    return ob_get_clean();
}

function clr_add_inline_styles() {
    ?>
    <style>
        form {
            max-width: 400px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background: #f9f9f9;
        }
        form input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
        }
        form button {
            width: 100%;
            padding: 10px;
            background-color: #0073aa;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        form button:hover {
            background-color: #005177;
        }
    </style>
    <?php
}
add_action('wp_head', 'clr_add_inline_styles');




// Update Admin Dashboard to Show Roles
function clr_admin_dashboard() {
    if (!is_user_logged_in()) {
        return '<p>You need to <a href="' . esc_url(wp_login_url()) . '">login</a> to access the admin dashboard.</p>';
    }

    $current_user = wp_get_current_user();

    // Restrict access to admins only
    if (!in_array('administrator', $current_user->roles)) {
        return '<p>You do not have permission to access this page.</p>';
    }

    $args = array(
        'post_type' => 'post', 
        'post_status' => array('publish', 'draft'), 
        'posts_per_page' => -1, 
    );

    $all_posts = new WP_Query($args);

    ob_start();

    echo '<h2>All User Posts</h2>';

    if ($all_posts->have_posts()) {
        echo '<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">';
        echo '<thead>';
        echo '<tr>';
        echo '<th style="border: 1px solid #ddd; padding: 8px;">Post Title</th>';
        echo '<th style="border: 1px solid #ddd; padding: 8px;">Author</th>';
        echo '<th style="border: 1px solid #ddd; padding: 8px;">Role</th>';
        echo '<th style="border: 1px solid #ddd; padding: 8px;">Status</th>';
        echo '<th style="border: 1px solid #ddd; padding: 8px;">Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        while ($all_posts->have_posts()) {
            $all_posts->the_post();
            $post_id = get_the_ID();
            $author_id = get_the_author_meta('ID');
            $author_name = get_the_author();
            $author_role = implode(', ', get_userdata($author_id)->roles);
            $post_status = get_post_status($post_id);
            $edit_link = get_edit_post_link($post_id);
            $delete_link = esc_url(add_query_arg(array('delete_post_id' => $post_id), home_url('/admin-dashboard')));

            echo '<tr>';
            echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html(get_the_title()) . '</td>';
            echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($author_name) . '</td>';
            echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($author_role) . '</td>';
            echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($post_status) . '</td>';
            echo '<td style="border: 1px solid #ddd; padding: 8px;">';
            echo '<a href="' . esc_url($edit_link) . '" class="button">Edit</a> ';
            echo '<a href="' . $delete_link . '" class="button delete-button" onclick="return confirm(\'Are you sure you want to delete this post?\');">Delete</a>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No posts found.</p>';
    }

    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode('clr_admin_dashboard', 'clr_admin_dashboard');





function clr_handle_admin_post_deletion() {
    if (isset($_GET['delete_post_id']) && is_user_logged_in()) {
        $post_id = intval($_GET['delete_post_id']);
        $post = get_post($post_id);

      
        if ($post && current_user_can('delete_post', $post_id)) {
            wp_delete_post($post_id, true); 
            wp_redirect(home_url('/admin-dashboard'));

            exit;
        } else {
            wp_die('You admin are not authorized to delete this post.');
        }
    }
}
add_action('init', 'clr_handle_admin_post_deletion');






function clr_user_dashboard() {
    if (!is_user_logged_in()) {
        return '<p>You need to <a href="' . esc_url(wp_login_url()) . '">login</a> to access the dashboard.</p>';
    }

    $current_user = wp_get_current_user();
    
    // Display the Create Post Form
    echo do_shortcode('[clr_create_post]');

    $args = array(
        'author' => $current_user->ID,
        'post_status' => array('publish', 'draft'),
        'posts_per_page' => -1,
    );

    $user_posts = new WP_Query($args);

    ob_start();

    echo '<h2>Your Posts</h2>';

    if ($user_posts->have_posts()) {
        echo '<ul>';
        while ($user_posts->have_posts()) {
            $user_posts->the_post();
            ?>
            <li>
                <h3><?php the_title(); ?></h3>
                <p><?php the_excerpt(); ?></p>
                <a href="<?php echo esc_url(get_edit_post_link()); ?>" class="button">Edit</a>
                <a href="<?php echo esc_url(add_query_arg(array('userdelete_post_id' => get_the_ID()), home_url('/user-dashboard'))); ?>" class="button redelete-button" onclick="return confirm('Are you sure you want to delete this post?');">Delete</a>
            </li>
            <?php
        }
        echo '</ul>';
    } else {
        echo '<p>You have not created any posts yet.</p>';
    }

    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode('clr_dashboard', 'clr_user_dashboard');




function clr_handle_post_deletion() {
    if (isset($_GET['userdelete_post_id']) && is_user_logged_in()) {
        $post_id = intval($_GET['userdelete_post_id']);
        $post = get_post($post_id);

        if ($post) {
            $current_user_id = get_current_user_id();
            error_log("Post Author: " . $post->post_author); 
            error_log("Current User ID: " . $current_user_id);
            
            if ($post->post_author == $current_user_id) {
                wp_delete_post($post_id, true);
                wp_redirect(home_url('/user-dashboard'));
                exit;
            } else {
                wp_die('You are not authorized to delete this post.');
            }
        } else {
            wp_die('Invalid post.');
        }
    }
}
add_action('init', 'clr_handle_post_deletion');





function clr_redirect_after_login($redirect_to, $request, $user) {
   
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('administrator', $user->roles)) {
           
            return home_url('/admin-dashboard');
        } else {
            
            return home_url('/user-dashboard');
        }
    }

    return $redirect_to; 
}
add_filter('login_redirect', 'clr_redirect_after_login', 10, 3);



function clr_restrict_admin_dashboard_access() {
    
    if (is_page('admin-dashboard')) {
     
        $current_user = wp_get_current_user();

        if (!in_array('administrator', $current_user->roles)) {
           
            wp_redirect(home_url('/user-dashboard'));
            exit;
        }
    }
}
add_action('template_redirect', 'clr_restrict_admin_dashboard_access');




function clr_custom_logout_redirect() {
   
    $redirect_url = home_url('/login-register'); 

    
    wp_redirect($redirect_url);
    exit;
}
add_action('wp_logout', 'clr_custom_logout_redirect');






// Shortcode to Display Create Post Form
function clr_create_post_form() {
    if (!is_user_logged_in() || !current_user_can('subscriber')) {
        return '<p>You do not have permission to create posts.</p>';
    }

    ob_start();
    ?>
    <form id="clr-create-post-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST">
        <h2>Create a New Post</h2>
        <input type="text" name="post_title" placeholder="Post Title" required>
        <textarea name="post_content" placeholder="Post Content" required></textarea>
        <input type="hidden" name="action" value="clr_create_post">
        <button type="submit">Create Post</button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('clr_create_post', 'clr_create_post_form');



function clr_handle_create_post() {
    if (!is_user_logged_in() || !current_user_can('subscriber')) {
        wp_die('You are not authorized to create posts.');
    }

    if (!isset($_POST['post_title'], $_POST['post_content'])) {
        wp_die('Please complete all fields.');
    }

    $post_data = array(
        'post_title'   => sanitize_text_field($_POST['post_title']),
        'post_content' => wp_kses_post($_POST['post_content']),
        'post_status'  => 'draft', 
        'post_author'  => get_current_user_id(),
    );

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        wp_die($post_id->get_error_message());
    }

    wp_redirect(home_url('/user-dashboard')); 
    exit;
}
add_action('admin_post_nopriv_clr_create_post', 'clr_handle_create_post');
add_action('admin_post_clr_create_post', 'clr_handle_create_post');
