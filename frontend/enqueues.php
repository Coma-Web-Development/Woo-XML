<?php


/**
 * Enqueue admin scripts and styles.
 */
function wooxml_admin_scripts() {
    wp_enqueue_script( 'wooxml-script', WOOXML_URL .'/assets/js/script.js', array( 'jquery' ), time(), false );
    $options = array( 
        'ajax_url'  => admin_url( 'admin-ajax.php' ),
        'nonce'     => wp_create_nonce( 'nonce' ),
    );
    wp_localize_script( 'wooxml-script', 'wooxmlOpts', $options );
}
add_action( 'admin_enqueue_scripts', 'wooxml_admin_scripts' );