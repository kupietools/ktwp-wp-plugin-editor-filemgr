<?php
/*
 * Plugin Name: KupieTools Editor File Manager
 * Plugin URI:        https://michaelkupietz.com/
 * Description:       Add Create New, Rename, and Download file options to Plugin File Editor and Theme File Editor admin screens.
 * Version:           1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Michael Kupietz
 * Author URI:        https://michaelkupietz.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://michaelkupietz.com/my-plugin/kupietools-editor-filemgr/
 * Text Domain:       mk-plugin
 * Domain Path:       /languages
 */


/**
 * Your code goes below.
 */


// Add rename and create file operations to Plugin and Theme editors
// // Set to true to load the newly created/renamed file, false to stay on current file
define('FILEOPS_LOAD_NEW_FILE', false);  // Change this to true or false as needed

add_action('admin_footer-plugin-editor.php', 'add_file_operations_interface');
add_action('admin_footer-theme-editor.php', 'add_file_operations_interface');
add_action('admin_init', 'handle_download_file');
add_action('admin_init', 'handle_file_operations');
function has_allowed_extension($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    $is_plugin_editor = strpos($_SERVER['REQUEST_URI'], 'plugin-editor.php') !== false;
    
    if ($is_plugin_editor) {
        $allowed_extensions = ['html', 'php', 'css', 'txt', 'js', 'rtf'];
    } else {
        // Theme editor
        $allowed_extensions = ['php', 'css'];
    }
    
    return in_array($ext, $allowed_extensions);
}



function handle_file_operations() {
    if (!current_user_can('edit_themes') && !current_user_can('edit_plugins')) {
        return;
    }

    if (!isset($_POST['file_ops_nonce']) || !wp_verify_nonce($_POST['file_ops_nonce'], 'file_ops_nonce')) {
        return;
    }

    $action = $_POST['action'] ?? '';
    
    if ($action === 'rename_file') {
        handle_rename_file();
    } elseif ($action === 'create_file') {
        handle_create_file();
    }
}

function handle_create_file() {
    if (!isset($_POST['new_file']) || !isset($_POST['file_path'])) {
        return;
    }

    $new_filename = sanitize_file_name($_POST['new_file']);
	 if (!has_allowed_extension($new_filename)) {
        wp_die('File type not allowed.');
    }

    $file_path = sanitize_text_field($_POST['file_path']);
    
    $is_plugin = isset($_GET['plugin']);
    
    if ($is_plugin) {
        $base_path = WP_PLUGIN_DIR;
        $redirect_page = 'plugin-editor.php';
    } else {
        $stylesheet = get_stylesheet();
        $base_path = get_theme_root() . '/' . $stylesheet;
        $redirect_page = 'theme-editor.php';
    }

    $new_path = $base_path . '/' . $file_path . '/' . $new_filename;

    if (file_exists($new_path)) {
        wp_die('File already exists.');
    }

    if (file_put_contents($new_path, "<?php\n// " . $new_filename . "\n") !== false) {
        $query_args = $is_plugin ? ['plugin' => $_GET['plugin']] : ['theme' => get_stylesheet()];
        
        if (FILEOPS_LOAD_NEW_FILE) {
            $query_args['file'] = ltrim($file_path . '/' . $new_filename, '/');
        }
        
        wp_redirect(add_query_arg($query_args, admin_url($redirect_page)));
        exit;
    }
    
    wp_die('Failed to create file.');
}

function handle_rename_file() {
    if (!isset($_POST['current_file']) || !isset($_POST['new_filename'])) {
        wp_die('Missing required fields.');
    }

    $current_file = sanitize_text_field($_POST['current_file']);
    $new_filename = sanitize_file_name($_POST['new_filename']);
	 if (!has_allowed_extension($new_filename)) {
        wp_die('File type not allowed.');
    }


    $is_plugin = isset($_GET['plugin']);
    
    if ($is_plugin) {
        $base_path = WP_PLUGIN_DIR;
        $redirect_page = 'plugin-editor.php';
    } else {
        $stylesheet = get_stylesheet();
        $base_path = get_theme_root() . '/' . $stylesheet;
        $redirect_page = 'theme-editor.php';
    }

    $old_path = $base_path . '/' . $current_file;
    $new_path = dirname($old_path) . '/' . $new_filename;

    if (!file_exists($old_path)) {
        wp_die('File does not exist: ' . $old_path);
    }
    
    if (!is_writable($old_path)) {
        wp_die('File is not writable: ' . $old_path);
    }

    if (!is_writable(dirname($old_path))) {
        wp_die('Directory is not writable: ' . dirname($old_path));
    }

    if (rename($old_path, $new_path)) {
        $query_args = $is_plugin ? ['plugin' => $_GET['plugin']] : ['theme' => get_stylesheet()];
        
        if (FILEOPS_LOAD_NEW_FILE) {
            $query_args['file'] = ltrim(str_replace($base_path . '/', '', $new_path), '/');
        }
        
        wp_redirect(add_query_arg($query_args, admin_url($redirect_page)));
        exit;
    }
    
    wp_die('Failed to rename file.');
}

function add_file_operations_interface() {
    ?>
    <style>
   
		  .file-actions {
            display: none;
            margin-left: 25%;
            font-size: 12px;
			  position: absolute;
			  z-index: 99;
			  box-shadow:4px 4px 4px 0 rgba(0,0,0,.15);
			  border-radius:6px;
				
        }
		
        .file-actions > a, .file-actions  > a:hover  {
            margin-left: 5px;
            text-decoration: none;
            color: #0073aa;
			display: inline-block !important; /* need this, not block, nesting block in span causes css weirdness */
			background: #e0f1ff;
			width:100%;
			margin-top: 1px;
		           padding: 2px 5px;
 
        }
		.file-actions  > a:hover  {color: #00AAFF;}
        #templateside li:hover > a > .file-actions {
            display: block; /* changed to block so always happens below filename, even if wraps, but if this causes gaps above dropdowns, change back to inline-block and find some other way */
        }
        .rename-dialog, .create-dialog {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            padding: 20px;
            border: 1px solid #ccc;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            z-index: 9999;
        }
        .dialog-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 9998;
        }
    </style>

    <div class="rename-dialog">
        <h3>Rename File</h3>
        <form method="post" action="">
            <?php wp_nonce_field('file_ops_nonce', 'file_ops_nonce'); ?>
            <input type="hidden" name="action" value="rename_file">
            <input type="hidden" name="current_file" value="">
            <input type="text" name="new_filename" placeholder="New filename" required>
            <button type="submit" class="button button-primary">Rename</button>
            <button type="button" class="button cancel-dialog">Cancel</button>
        </form>
    </div>

    <div class="create-dialog">
        <h3>Create New File</h3>
        <form method="post" action="">
            <?php wp_nonce_field('file_ops_nonce', 'file_ops_nonce'); ?>
            <input type="hidden" name="action" value="create_file">
            <input type="hidden" name="file_path" value="">
            <input type="text" name="new_file" placeholder="filename.php" required>
            <button type="submit" class="button button-primary">Create</button>
            <button type="button" class="button cancel-dialog">Cancel</button>
        </form>
    </div>

    <div class="dialog-overlay"></div>

    <script>
		
    jQuery(document).ready(function($) {
        function addFileOperations() {
            // Remove any existing file actions first
            $('.file-actions').remove();
            
            // Add action buttons only to actual files (items without child ul elements)
            $('#templateside li:not(:has(ul))').each(function() {
                var $li = $(this);
                var $link = $li.find('> a[role="treeitem"]');
                
                if ($link.length) {
                    var href = $link.attr('href');
                    var fileMatch = href.match(/file=([^&]+)/);
                    
                    if (fileMatch) {
                        var filePath = decodeURIComponent(fileMatch[1]);
                        var parentPath = filePath.split('/').slice(0, -1).join('/');
         var downloadUrl = '<?php echo admin_url(); ?>?' + 
    'file=' + encodeURIComponent(filePath) + 
    '&_wpnonce=' + '<?php echo wp_create_nonce("download_file_nonce"); ?>' +
    (window.location.href.includes('plugin-editor.php') ? '&plugin=1' : '');

var actions = $('<span class="file-actions">' +
    '<a href="#" class="rename-file" data-file="' + filePath + '">Rename</a>' +
    '<a href="#" class="create-file" data-path="' + parentPath + '">New File</a>' +
    '<a href="' + downloadUrl + '" class="download-file">Download</a>' +
    '</span>');

                        
                        $link.append(actions);
                    }
                }
            });

            // Add "New File" button to folders (items with child ul elements)
            $('#templateside li:has(ul)').each(function() {
                var $li = $(this);
                var $link = $li.find('> a[role="treeitem"]');
                
                if ($link.length) {
                    var href = $link.attr('href');
                    var fileMatch = href.match(/file=([^&]+)/);
                    
                    if (fileMatch) {
                        var filePath = decodeURIComponent(fileMatch[1]);
                        var folderPath = filePath.split('/').slice(0, -1).join('/');
                        
             var downloadUrl = '<?php echo admin_url(); ?>?' + 
    'file=' + encodeURIComponent(filePath) + 
    '&_wpnonce=' + '<?php echo wp_create_nonce("download_file_nonce"); ?>' +
    (window.location.href.includes('plugin-editor.php') ? '&plugin=1' : '');

var actions = $('<span class="file-actions">' +
    '<a href="#" class="rename-file" data-file="' + filePath + '">Rename</a>' +
    '<a href="#" class="create-file" data-path="' + parentPath + '">New File</a>' +
    '<a href="' + downloadUrl + '" class="download-file">Download</a>' +
    '</span>');

                        
                        $link.append(actions);
                    }
                }
            });
        }

        // Initial setup
        addFileOperations();

        // Re-add operations when WordPress' built-in tree is modified
        $(document).on('click', '#templateside a[role="treeitem"]', function() {
            setTimeout(addFileOperations, 100);
        });

        // Rename file
        $(document).on('click', '.rename-file', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var currentFile = $(this).data('file');
            $('.rename-dialog input[name="current_file"]').val(currentFile);
            $('.rename-dialog input[name="new_filename"]').val(currentFile.split('/').pop());
            $('.rename-dialog, .dialog-overlay').show();
        });

        // Create new file
        $(document).on('click', '.create-file', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var path = $(this).data('path');
            $('.create-dialog input[name="file_path"]').val(path);
            $('.create-dialog, .dialog-overlay').show();
        });

        // Close dialogs
        $('.cancel-dialog, .dialog-overlay').click(function() {
            $('.rename-dialog, .create-dialog, .dialog-overlay').hide();
        });
    });
    </script>
    <?php
}

function handle_download_file() {
    if (!current_user_can('edit_themes') && !current_user_can('edit_plugins')) {
        return;
    }

    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'download_file_nonce')) {
        return;
    }

    if (!isset($_GET['file'])) {
        return;
    }

    $file_path = sanitize_text_field($_GET['file']);
    $is_plugin = isset($_GET['plugin']);
    
    if ($is_plugin) {
        $base_path = WP_PLUGIN_DIR;
    } else {
        $stylesheet = get_stylesheet();
        $base_path = get_theme_root() . '/' . $stylesheet;
    }

    $full_path = $base_path . '/' . $file_path;

    if (!file_exists($full_path)) {
        wp_die('File does not exist.');
    }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($full_path) . '"');
    header('Content-Length: ' . filesize($full_path));
    readfile($full_path);
    exit;
}






?>