<?php
/**
 * Plugin Name: Easy Event Creator
 * Plugin URI: 
 * Description: El plugin Nombre del Plugin te permite crear y mostrar eventos en tu sitio web de forma sencilla. Puedes crear eventos personalizados con información detallada como tipo de evento, fecha, descripción y URL. Luego, puedes mostrar estos eventos en una página específica utilizando el shortcode [eventos].
 * Version: 1.0.0
 * Author: MXideas
 * Author URI: https://mxideas.com
 **/

function event_creator_enqueue_styles() {
    wp_enqueue_style( 'event-creator-styles', plugins_url( '/style.css', __FILE__ ) );

    $event_box_height = get_option('event_box_height', '400px');
    $custom_css = '.event { height: ' . $event_box_height . '; }';
    wp_add_inline_style( 'event-creator-styles', $custom_css );
}
add_action( 'wp_enqueue_scripts', 'event_creator_enqueue_styles' );

// Agregamos una función para crear el tipo de post "evento"
function create_event_post_type() {
    register_post_type('evento',
        array(
            'labels' => array(
                'name' => __('Eventos'),
                'singular_name' => __('Evento')
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'taxonomies' => array('category', 'post_tag'),
        )
    );
}
add_action('init', 'create_event_post_type');

// Agregamos una función para crear los campos personalizados del tipo de post "evento"
function add_event_meta_box() {
    add_meta_box(
        'event_meta_box',
        __('Información del Evento'),
        'render_event_meta_box',
        'evento',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_event_meta_box');

// Agregamos una función para mostrar los campos personalizados en la página de creación de un evento
function render_event_meta_box($post) {
    wp_nonce_field(basename(__FILE__), 'event_meta_box_nonce');
    $event_type = get_post_meta($post->ID, 'event_type', true);
    $event_date = get_post_meta($post->ID, 'event_date', true);
    $event_url = get_post_meta($post->ID, 'event_url', true);
    ?>
    <table class="form-table">
        <tr>
            <th><label for="event_type"><?php _e('Tipo de Evento'); ?></label></th>
            <td><input type="text" id="event_type" name="event_type" value="<?php echo esc_attr($event_type); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="event_date"><?php _e('Fecha del Evento'); ?></label></th>
            <td><input type="date" id="event_date" name="event_date" value="<?php echo esc_attr($event_date); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="event_url"><?php _e('URL del Evento'); ?></label></th>
            <td><input type="url" id="event_url" name="event_url" value="<?php echo esc_url($event_url); ?>" class="regular-text" /></td>
        </tr>
       
    </table>
    <?php
}

// Agregamos una función para guardar los valores de los campos personalizados al crear o editar un evento
function save_event_meta($post_id) {
    if (!isset($_POST['event_meta_box_nonce']) || !wp_verify_nonce($_POST['event_meta_box_nonce'], basename(__FILE__))) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (isset($_POST['post_type']) && 'evento' == $_POST['post_type']) {
        if (!current_user_can('edit_page', $post_id)) {
            return;
        }
    } else {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    if (isset($_POST['event_type'])) {
        update_post_meta($post_id, 'event_type', sanitize_text_field($_POST['event_type']));
    }
        if (isset($_POST['event_date'])) {
        update_post_meta($post_id, 'event_date', sanitize_text_field($_POST['event_date']));
    }
    if (isset($_POST['event_url'])) {
        update_post_meta($post_id, 'event_url', esc_url_raw($_POST['event_url']));
    }
    
}
add_action('save_post', 'save_event_meta');


// Agregamos una función para mostrar los eventos en la página web

function display_events() {
    $show_event_title = get_option('show_event_title', true);
    $show_event_type = get_option('show_event_type', true);
    $show_event_date = get_option('show_event_date', true);
    $show_event_description = get_option('show_event_description', true);
    $show_event_url = get_option('show_event_url', true);
    $show_event_image = get_option('show_event_image', true);

    $events_query = new WP_Query( array(
        'post_type' => 'evento',
        'posts_per_page' => -1,
        'orderby' => 'meta_value',
        'meta_key' => 'event_date',
        'order' => 'ASC',
    ) );

    if ( $events_query->have_posts() ) {
        echo '<div class="event-grid">';

        while ( $events_query->have_posts() ) {
            $events_query->the_post();
            $event_title = get_the_title();
            $event_type = get_post_meta( get_the_ID(), 'event_type', true );
            $event_date = get_post_meta( get_the_ID(), 'event_date', true );
            $event_url = get_post_meta( get_the_ID(), 'event_url', true );
            $event_description = wp_strip_all_tags( get_the_content() );

            // Si no se especifica la URL del evento, mostrar la URL del post
            if ( empty( $event_url ) ) {
                $event_url = get_permalink();
                $target = '_self'; // Abrir en la misma página
            } else {
                $target = '_blank'; // Abrir en una nueva pestaña
            }

            ?>
            <div class="event">
                <?php if ( $show_event_image && has_post_thumbnail() ) { ?>
                    <div class="event-image">
                        <img src="<?php echo esc_url( get_the_post_thumbnail_url( get_the_ID(), 'large' ) ); ?>" alt="<?php echo esc_attr( $event_title ); ?>" />
                        <?php if ($show_event_type) { ?>
                            <span class="event-type"><?php echo esc_html( $event_type ); ?></span>
                        <?php } ?>
                    </div>
                <?php } ?>
                <div class="event-details">
                    <?php if ($show_event_title) { ?>
                        <h2><?php echo esc_html( $event_title ); ?></h2>
                    <?php } ?>
                   
                    <?php if ($show_event_date) { ?>
                        <div class="event-date">
                            <span class="dashicons dashicons-calendar"></span> <?php echo esc_html( $event_date ); ?>
                        </div>
                    <?php } ?>
                    <?php if ($show_event_description) { ?>
                        <p><?php echo esc_html( mb_substr( $event_description, 0, 150 ) ); ?></p>
                    <?php } ?>
                    <?php if ($show_event_url) { ?>
                        <a href="<?php echo esc_url( $event_url ); ?>" class="event-url" target="<?php echo esc_attr( $target ); ?>"><?php _e( 'Ver más' ); ?></a>
                    <?php } ?>
                </div>
            </div>
            <?php
        }

        echo '</div>';
        wp_reset_postdata();
    } else {
        echo '<p>' . __( 'No se encontraron eventos.' ) . '</p>';
    }
}











// Agregar un elemento de menú "Configuración" en el panel de administración
function add_event_plugin_menu() {
    add_submenu_page(
        'edit.php?post_type=evento',
        'Configuración',
        'Configuración',
        'manage_options',
        'event-plugin-settings',
        'event_plugin_settings_page'
    );
}
add_action('admin_menu', 'add_event_plugin_menu');


// Página de configuración del plugin
function event_plugin_settings_page() {
    $event_box_height = get_option('event_box_height');
    ?>
    <div class="wrap">
         <h1>Configuración del Plugin de Eventos</h1>
        <div class="wrap">
<p>Aquí puedes encontrar instrucciones sobre cómo usar el plugin de eventos.</p>
<p>Inserta el shortcode <code>[eventos]</code> en cualquier página para mostrar la lista de eventos.</p>
</div>

        <form method="post" action="options.php">
            <?php settings_fields('event-plugin-settings'); ?>
            <?php do_settings_sections('event-plugin-settings'); ?>

            <table class="form-table">
                <tr>
                    <th><label for="event_box_height"><?php _e('Altura de las Cajas de Eventos'); ?></label></th>
                    <td><input type="text" id="event_box_height" name="event_box_height" value="<?php echo esc_attr($event_box_height); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Mostrar Título del evento:</th>
                    <td>
                        <label>
                            <input type="radio" name="show_event_title" value="1" <?php checked(get_option('show_event_title'), '1'); ?> />
                            Mostrar
                        </label>
                        <br />
                        <label>
                            <input type="radio" name="show_event_title" value="0" <?php checked(get_option('show_event_title'), '0'); ?> />
                            Ocultar
                        </label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Mostrar Tipo de evento:</th>
                    <td>
                        <label>
                            <input type="radio" name="show_event_type" value="1" <?php checked(get_option('show_event_type'), '1'); ?> />
                            Mostrar
                        </label>
                        <br />
                        <label>
                            <input type="radio" name="show_event_type" value="0" <?php checked(get_option('show_event_type'), '0'); ?> />
                            Ocultar
                        </label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Mostrar fecha de evento:</th>
                    <td>
                        <label>
                            <input type="radio" name="show_event_date" value="1" <?php checked(get_option('show_event_date'), '1'); ?> />
                            Mostrar
                        </label>
                        <br />
                        <label>
                            <input type="radio" name="show_event_date" value="0" <?php checked(get_option('show_event_date'), '0'); ?> />
                            Ocultar
                        </label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Mostrar Imagen del evento:</th>
                    <td>
                        <label>
                            <input type="radio" name="show_event_image" value="1" <?php checked(get_option('show_event_image'), '1'); ?> />
                            Mostrar
                        </label>
                        <br />
                        <label>
                            <input type="radio" name="show_event_image" value="0" <?php checked(get_option('show_event_image'), '0'); ?> />
                            Ocultar
                        </label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Mostrar Descripción del evento:</th>
                    <td>
                        <label>
                            <input type="radio" name="show_event_description" value="1" <?php checked(get_option('show_event_description'), '1'); ?> />
                            Mostrar
                        </label>
                        <br />
                        <label>
                            <input type="radio" name="show_event_description" value="0" <?php checked(get_option('show_event_description'), '0'); ?> />
                            Ocultar
                        </label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Mostrar URL del evento:</th>
                    <td>
                        <label>
                            <input type="radio" name="show_event_url" value="1" <?php checked(get_option('show_event_url'), '1'); ?> />
                            Mostrar
                        </label>
                        <br />
                        <label>
                            <input type="radio" name="show_event_url" value="0" <?php checked(get_option('show_event_url'), '0'); ?> />
                            Ocultar
                        </label>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}



//

function register_event_plugin_settings() {
    // Registrar el campo de configuración para la altura de las cajas de eventos
    register_setting('event-plugin-settings', 'event_box_height');
    register_setting('event-plugin-settings', 'show_event_type');
    register_setting('event-plugin-settings', 'show_event_date');
    register_setting('event-plugin-settings', 'show_event_description');
    register_setting('event-plugin-settings', 'show_event_url');
    register_setting('event-plugin-settings', 'show_event_title');
    register_setting('event-plugin-settings', 'show_event_image');
    
}
add_action('admin_init', 'register_event_plugin_settings');



// Agregamos una función para mostrar los eventos en una página específica usando el shortcode [eventos]
function eventos_shortcode() {
    ob_start();
    display_events();
    return ob_get_clean();
}
add_shortcode('eventos', 'eventos_shortcode');


