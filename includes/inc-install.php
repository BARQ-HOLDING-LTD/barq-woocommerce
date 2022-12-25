<?php

class Barq_Install {

    /**
     * Barq_Install constructor.
     */
    public function __construct() {
    }

    /**
     * Install Barq.
     */
    public static function install() {
        self::create_files();
    }

    /**
     * Create files/directories.
     */
    private static function create_files() {
        // Bypass if filesystem is read-only and/or non-standard upload system is used
        if ( apply_filters( 'woocommerce_install_skip_create_files', false ) ) {
            return;
        }
        $files = array(
            array(
                'base' 		=> BARQ_LOGS,
                'file' 		=> '.htaccess',
                'content' 	=> 'deny from all',
            ),
            array(
                'base' 		=> BARQ_LOGS,
                'file' 		=> 'index.html',
                'content' 	=> '',
            ),
        );
        foreach ( $files as $file ) {
            if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
                if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {
                    fwrite( $file_handle, $file['content'] );
                    fclose( $file_handle );
                }
            }
        }
    }
}