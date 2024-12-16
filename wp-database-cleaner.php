<?php
/*
Plugin Name: WP Database Cleaner
Description: Optimiza y limpia tu base de datos de WordPress.
Version: 1.0
Author: Alexis Olivero
*/

// Evita el acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Incluye los archivos necesarios
include_once plugin_dir_path(__FILE__) . 'includes/optimizer.php';
include_once plugin_dir_path(__FILE__) . 'includes/admin-interface.php';

// Activa el plugin
register_activation_hook(__FILE__, 'wp_database_cleaner_activate');
function wp_database_cleaner_activate() {
    // Código de activación
}

// Desactiva el plugin
register_deactivation_hook(__FILE__, 'wp_database_cleaner_deactivate');
function wp_database_cleaner_deactivate() {
    // Código de desactivación
}

// Agrega un menú en el dashboard
add_action('admin_menu', 'wp_database_cleaner_menu');
function wp_database_cleaner_menu() {
    add_menu_page(
        'WP Database Cleaner', // Título de la página
        'DB Cleaner', // Título del menú
        'manage_options', // Capacidad
        'wp-database-cleaner', // Slug del menú
        'wp_database_cleaner_dashboard', // Función que muestra el contenido
        'dashicons-database', // Icono del menú
        20 // Posición
    );
}

// Enqueue custom styles
add_action('admin_enqueue_scripts', 'wp_database_cleaner_enqueue_styles');
function wp_database_cleaner_enqueue_styles() {
    wp_enqueue_style('wp-database-cleaner-styles', plugin_dir_url(__FILE__) . 'assets/css/styles.css');
}

// Register AJAX actions
add_action('wp_ajax_wp_database_cleaner_optimize_db', 'wp_database_cleaner_optimize_db');
add_action('wp_ajax_wp_database_cleaner_clean_revisions', 'wp_database_cleaner_clean_revisions');
add_action('wp_ajax_wp_database_cleaner_clean_transients', 'wp_database_cleaner_clean_transients');
add_action('wp_ajax_wp_database_cleaner_clean_spam_comments', 'wp_database_cleaner_clean_spam_comments');
add_action('wp_ajax_wp_database_cleaner_clean_all', 'wp_database_cleaner_clean_all');

// Función que muestra el contenido del escritorio del plugin
function wp_database_cleaner_dashboard() {
    ?>
    <div class="wrap wp-database-cleaner-wrap">
        <h1>WP Database Cleaner</h1>
        <p>Bienvenido al escritorio de WP Database Cleaner. Aquí puedes optimizar y limpiar tu base de datos.</p>
        <form method="post" action="">
            <input type="submit" name="optimize_db" class="button button-primary" value="Optimizar Base de Datos">
            <input type="submit" name="clean_revisions" class="button button-secondary" value="Limpiar Revisiones">
            <input type="submit" name="clean_transients" class="button button-secondary" value="Limpiar Transients">
            <input type="submit" name="clean_spam_comments" class="button button-secondary" value="Limpiar Comentarios Spam">
            <input type="submit" name="clean_all" class="button button-danger" value="Limpiar Todo">
        </form>
        <h2>Estadísticas de la Base de Datos</h2>
        <ul class="wp-database-cleaner-stats">
            <li>Total de tablas: <span id="table-count"><?php echo wp_database_cleaner_get_table_count(); ?></span></li>
            <li>Total de revisiones: <span id="revision-count"><?php echo wp_database_cleaner_get_revision_count(); ?></span></li>
            <li>Total de transients: <span id="transient-count"><?php echo wp_database_cleaner_get_transient_count(); ?></span></li>
            <li>Total de comentarios spam: <span id="spam-comment-count"><?php echo wp_database_cleaner_get_spam_comment_count(); ?></span></li>
        </ul>
        <div id="wp-database-cleaner-message"></div>
    </div>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.wp-database-cleaner-wrap form');
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                const formData = new FormData(form);
                const action = formData.get('optimize_db') ? 'wp_database_cleaner_optimize_db' :
                               formData.get('clean_revisions') ? 'wp_database_cleaner_clean_revisions' :
                               formData.get('clean_transients') ? 'wp_database_cleaner_clean_transients' :
                               formData.get('clean_spam_comments') ? 'wp_database_cleaner_clean_spam_comments' :
                               'wp_database_cleaner_clean_all';
                formData.append('action', action);
                fetch(ajaxurl, {
                    method: 'POST',
                    body: new URLSearchParams(formData)
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('wp-database-cleaner-message').innerHTML = data.data.message;
                    document.getElementById('table-count').textContent = data.data.table_count;
                    document.getElementById('revision-count').textContent = data.data.revision_count;
                    document.getElementById('transient-count').textContent = data.data.transient_count;
                    document.getElementById('spam-comment-count').textContent = data.data.spam_comment_count;
                });
            });
        });
    </script>
    <?php

    // Maneja las acciones del formulario
    if (isset($_POST['optimize_db'])) {
        wp_database_cleaner_optimize_db();
    }
    if (isset($_POST['clean_revisions'])) {
        wp_database_cleaner_clean_revisions();
    }
    if (isset($_POST['clean_transients'])) {
        wp_database_cleaner_clean_transients();
    }
    if (isset($_POST['clean_spam_comments'])) {
        wp_database_cleaner_clean_spam_comments();
    }
    if (isset($_POST['clean_all'])) {
        wp_database_cleaner_clean_all();
    }
}

// Función para optimizar la base de datos
function wp_database_cleaner_optimize_db() {
    global $wpdb;
    $tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
    foreach ($tables as $table) {
        $wpdb->query("OPTIMIZE TABLE {$table[0]}");
    }
    wp_send_json_success(array(
        'message' => '<div class="updated"><p>Base de datos optimizada.</p></div>',
        'table_count' => wp_database_cleaner_get_table_count(),
        'revision_count' => wp_database_cleaner_get_revision_count(),
        'transient_count' => wp_database_cleaner_get_transient_count(),
        'spam_comment_count' => wp_database_cleaner_get_spam_comment_count()
    ));
}

// Función para limpiar revisiones
function wp_database_cleaner_clean_revisions() {
    global $wpdb;
    $count = $wpdb->query("DELETE FROM $wpdb->posts WHERE post_type = 'revision'");
    wp_send_json_success(array(
        'message' => '<div class="updated"><p>' . $count . ' revisiones limpiadas.</p></div>',
        'table_count' => wp_database_cleaner_get_table_count(),
        'revision_count' => wp_database_cleaner_get_revision_count(),
        'transient_count' => wp_database_cleaner_get_transient_count(),
        'spam_comment_count' => wp_database_cleaner_get_spam_comment_count()
    ));
}

// Función para limpiar transients
function wp_database_cleaner_clean_transients() {
    global $wpdb;
    $count = $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_%'");
    wp_send_json_success(array(
        'message' => '<div class="updated"><p>' . $count . ' transients limpiados.</p></div>',
        'table_count' => wp_database_cleaner_get_table_count(),
        'revision_count' => wp_database_cleaner_get_revision_count(),
        'transient_count' => wp_database_cleaner_get_transient_count(),
        'spam_comment_count' => wp_database_cleaner_get_spam_comment_count()
    ));
}

// Función para limpiar comentarios spam
function wp_database_cleaner_clean_spam_comments() {
    global $wpdb;
    $count = $wpdb->query("DELETE FROM $wpdb->comments WHERE comment_approved = 'spam'");
    wp_send_json_success(array(
        'message' => '<div class="updated"><p>' . $count . ' comentarios spam limpiados.</p></div>',
        'table_count' => wp_database_cleaner_get_table_count(),
        'revision_count' => wp_database_cleaner_get_revision_count(),
        'transient_count' => wp_database_cleaner_get_transient_count(),
        'spam_comment_count' => wp_database_cleaner_get_spam_comment_count()
    ));
}

// Función para limpiar todas las tablas especificadas
function wp_database_cleaner_clean_all() {
    global $wpdb;
    // Limpiar revisiones
    $wpdb->query("DELETE FROM $wpdb->posts WHERE post_type = 'revision'");
    // Limpiar transients
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_%'");
    // Limpiar comentarios spam
    $wpdb->query("DELETE FROM $wpdb->comments WHERE comment_approved = 'spam'");
    wp_send_json_success(array(
        'message' => '<div class="updated"><p>Todos los datos basura han sido limpiados.</p></div>',
        'table_count' => wp_database_cleaner_get_table_count(),
        'revision_count' => wp_database_cleaner_get_revision_count(),
        'transient_count' => wp_database_cleaner_get_transient_count(),
        'spam_comment_count' => wp_database_cleaner_get_spam_comment_count()
    ));
}

// Función para obtener el total de tablas
function wp_database_cleaner_get_table_count() {
    global $wpdb;
    $tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
    return count($tables);
}

// Función para obtener el total de revisiones
function wp_database_cleaner_get_revision_count() {
    global $wpdb;
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'revision'");
    return $count;
}

// Función para obtener el total de transients
function wp_database_cleaner_get_transient_count() {
    global $wpdb;
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->options WHERE option_name LIKE '_transient_%'");
    return $count;
}

// Función para obtener el total de comentarios spam
function wp_database_cleaner_get_spam_comment_count() {
    global $wpdb;
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved = 'spam'");
    return $count;
}
?>
