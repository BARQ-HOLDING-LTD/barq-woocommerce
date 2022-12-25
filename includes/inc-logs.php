<?php

class Barq_Log {

    public $ext = '.log';

    /**
     * Barq_Log constructor.
     */
    public function __construct() {
    }

    /**
	 * specify the file that will be used to log
     * @param $filename
     * @param $status
     * @param $date
     * @return null|string
     */
    public function check_file_name( $filename, $status, $date ) {
        if ( empty( $filename ) ) {
            return $status . '-' . $date . $this->ext;
		}
        return $filename . '-' . $date . $this->ext;
    }

    /**
     * Add Custom Barq Log
     * @param $content
     * @param $status
     * @param null $filename
     * @internal param $log
     */
    public function write( $content, $status = 'info', $filename = null ) {
        $file = $this->check_file_name( $filename, $status, date("Y-m-d") );
        $log_time = '[' . date('Y-m-d H:i:s') . '] - ';
        if ( is_array( $content ) || is_object( $content ) ) {
            error_log( $log_time . print_r( $content, true ) . PHP_EOL, 3, trailingslashit( BARQ_LOGS ) . $file );
        } else {
            error_log( $log_time . $content . PHP_EOL, 3, trailingslashit( BARQ_LOGS ) . $file );
        }
    }
}