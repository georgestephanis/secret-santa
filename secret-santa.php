<?php

/*
 * Plugin Name: Secret Santa
 * Plugin URI: http://github.com/georgestephanis/secret-santa
 * Description: A plugin that lets you organize Secret Santa groups.
 * Author: George Stephanis
 * Version: 0.1-dev
 * Author URI: https://stephanis.info
 */

class Secret_Santa {
    public static function add_hooks() {
        add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
    }

    public static function admin_menu() {
        add_users_page( __( 'Secret Santa' ), __( 'Secret Santa' ), 'manage_options', 'secret-santa', array( __CLASS__, 'admin_page' ) );
    }

    public static function admin_page() {
        add_action( 'admin_print_footer_scripts', array( __CLASS__, 'js_templates' ), 1 );

        wp_enqueue_style( 'secret-santa', plugins_url( 'admin-page.css', __FILE__ ) );
        wp_enqueue_script( 'secret-santa', plugins_url( 'admin-page.js', __FILE__ ), array( 'wp-util', 'jquery' ), false, true );
        wp_localize_script( 'secret-santa', 'secretSanta', array(
            'elves' => self::get_users(),
        ) );
        ?>
        <div class="wrap" id="secret-santa-page">
            <h1><?php esc_html_e( 'Secret Santa' ); ?></h1>

            <h2><?php esc_html_e( 'The following users are participating in Secret Santa' ); ?></h2>
            <table id="elves-table" class="wp-list-table widefat striped">
                <thead>
                <tr>
                    <th scope="col"><?php esc_html_e( 'User' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Restrictions' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Shipping To' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Receiving From' ); ?></th>
                </tr>
                </thead>
                <tfoot>
                <tr>
                    <th scope="col"><?php esc_html_e( 'User' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Restrictions' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Shipping To' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Receiving From' ); ?></th>
                </tr>
                </tfoot>
                <tbody>
                <tr class="no-items">
                    <td class="colspanchange" colspan="4"><?php esc_html_e( 'No participants yet.' ); ?></td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * JS Templates.
     */
    public static function js_templates() {
        ?>
        <script type="text/html" id="tmpl-elf-card">
            <div class="elf-card wp-clearfix">
                <div class="elfvatar">
                    <img src="{{ data.avatar_url }}" />
                </div>
                <h4 class="name">{{ data.name }}</h4>
                <small class="address">{{ data.address }}</small>
                <strong class="country">{{ data.country }}</strong>
            </div>
        </script>

        <script type="text/html" id="tmpl-elf-row">
               <tr>
                   <td>{{{ data.elf_card }}}</td>
                   <td>
                       <ul>
                           {{{ data.restrictions }}}
                       </ul>
                   </td>
                   <td>{{{ data.shipping_to_card }}}</td>
                   <td>{{{ data.receiving_from_card }}}</td>
               </tr>
        </script>

        <script type="text/html" id="tmpl-elf-restriction">
            <li>{{ data.restriction }}</li>
        </script>
        <?php
    }

    public static function get_users() {
        return array(
            'bobsmith' => array(
                'name' => 'Bob Smith',
                'avatar_url' => 'https://dummyimage.com/300x300/000/fff',
                'address' => "123 Anystreet\r\nMytown, PA 17603",
                'country' => 'USA',
                'restrictions' => array(
                    'shipping_to' => 'any',
                    'receiving_from' => 'any',
                ),
            )
        );
    }
}

Secret_Santa::add_hooks();
