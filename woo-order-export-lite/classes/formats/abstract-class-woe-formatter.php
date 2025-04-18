<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

abstract class WOE_Formatter {
	var $has_output_filter;
	var $mode;
	var $settings;
	var $offset;

	/**
	 * @var WC_Order_Export_Labels[]
	 */
	var $labels;

	var $handle;
	var $format;
	var $field_formats;
	var $date_format;
	var $auto_format_dates = true;
	var $format_number_fields;
	var $counter_value;

	var $filename;

	var $decimals;
	var $decimal_separator;
	var $thousands_separator;

	public function __construct(
		$mode,
		$filename,
		$settings,
		$format,
		$labels,
		$field_formats,
		$date_format,
		$offset
	) {
		$this->has_output_filter = has_filter( "woe_{$format}_output_filter" );
		$this->mode              = $mode;
		$this->filename          = $filename;
		$this->settings          = $settings;
		$this->offset            = $offset;
		$this->labels            = $labels;
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$this->handle            = fopen( $filename, 'a' );
		if ( ! $this->handle ) {
			throw new Exception( esc_html($filename) . esc_html__( 'can not open for output', 'woo-order-export-lite' ) );
		}
		$this->format               = $format;
		$this->format_number_fields = ! empty( $this->settings['global_job_settings']['format_number_fields'] ) ? $this->settings['global_job_settings']['format_number_fields'] : false;

		// format for cells
		$this->field_formats = $field_formats;
		$this->date_format   = apply_filters( "woe_{$format}_date_format", $date_format );

		// separators
		$this->decimals            = wc_get_price_decimals();
		$this->decimal_separator   = wc_get_price_decimal_separator();
		$this->thousands_separator = apply_filters( 'woe_thousands_separator', '' );

		$this->counter_value = $this->get_counter();
		if ( ! $this->counter_value ) {
			$this->counter_value = 1;
			$this->set_counter( $this->counter_value );
		}
	}

	function __destruct() {
		$this->set_counter( $this->counter_value );
	}

	public function start( $data = '' ) {
		do_action( "woe_formatter_start", $data );
		do_action( "woe_formatter_" . $this->format . "_start", $data );
	}

	public function output( $rec ) {
		$this->handle = apply_filters( "woe_formatter_set_handler_for_" . $this->format . "_row", $this->handle );
		if ( $this->auto_format_dates ) {
			$rec = $this->format_fields( $rec, 'date' );
		}
		if ( $this->format_number_fields ) {
			$rec = $this->format_fields( $rec, 'money' );
			$rec = $this->format_fields( $rec, 'number' );
		}
		if ( isset( $rec['line_number'] ) ) {
			$rec['line_number'] = $this->counter_value;
			$this->counter_value ++;
		}

		return $rec;
	}

	public function finish() {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $this->handle );
		$this->delete_counter();
		do_action( "woe_formatter_finish", $this );
		do_action( "woe_formatter_" . $this->format . "_finished", $this );
	}

	public function finish_partial() {
		// child must fully implement this method
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $this->handle );
		do_action( "woe_formatter_finish_partial", $this );
		do_action( "woe_formatter_" . $this->format . "_finished_partially", $this );
	}

	public function truncate() {
		ftruncate( $this->handle, 0 );
	}

	protected function convert_literals( $s ) {
		$s = str_replace( '\r', "\r", $s );
		$s = str_replace( '\t', "\t", $s );
		$s = str_replace( '\n', "\n", $s );

		return $s;
	}

	protected function format_fields( $rec, $format_type ) {
		if ( isset( $this->field_formats['order'][ $format_type ] ) ) {
			foreach ( $rec as $field => $value ) {
				if ( $field == 'products' || $field == 'coupons' ) {
					if ( isset( $this->field_formats[ $field ][ $format_type ] ) ) {
						foreach ( $value as $item_id => $item_fields ) {
							foreach ( $item_fields as $item_field => $item_field_value ) {
								if ( in_array( $item_field, $this->field_formats[ $field ][ $format_type ] ) ) {
									$rec[ $field ][ $item_id ][ $item_field ] = $this->format_field( $format_type,
										$item_field_value );
								}
							}
						}
					}
				} else {
					if ( in_array( $field, $this->field_formats['order'][ $format_type ] ) ) {
						$rec[ $field ] = $this->format_field( $format_type, $value );
					}
				}
			}
		}

		return $rec;
	}

	protected function format_field( $type, $field_value ) {
		$func = array( $this, "format_{$type}_field" );

		return is_callable( $func ) ? call_user_func( $func, $field_value ) : $field_value;
	}

	protected function format_date_field( $field_value ) {
		// 20211208 is not timestamp! too, strtotime() can parse it
		if ( ! WOE_Formatter::is_valid_time_stamp( $field_value ) OR preg_match( '#^\d{8}$#', $field_value ) ) {
			$ts = strtotime( $field_value );
		} else {
			$ts = $field_value;
		}

		if ( $ts ) {
			$new_value = gmdate( $this->date_format, $ts );
		} else {
			$new_value = '';
		}

		$new_value = apply_filters( 'woe_format_date', $new_value, $field_value, $this->date_format );

		return $new_value;
	}

	public static function is_valid_time_stamp( $timestamp ) {
		return ((string) (int) $timestamp === $timestamp) 
			&& ($timestamp <= PHP_INT_MAX)
			&& ($timestamp >= ~PHP_INT_MAX);
	}

	protected function format_money_field( $field_value ) {
		$new_value = number_format(
			floatval( $field_value ),
			$this->decimals,
			$this->decimal_separator,
			$this->thousands_separator
		);
		$new_value = apply_filters( 'woe_format_money', $new_value, $field_value );

		return $new_value;
	}

	protected function format_number_field( $field_value ) {
		$new_value = $field_value; //as is!
		$new_value = apply_filters( 'woe_format_numbers', $new_value, $field_value );
		return $new_value;
	}


	protected function generate_key() {
		return $this->mode . '+' . $this->filename;
	}

	protected function delete_counter() {
		delete_transient( $this->generate_key() );
	}

	protected function set_counter( $count_value ) {
		$this->counter_value = $count_value;
		if ( $this->mode != 'preview' ) {
			set_transient( $this->generate_key(), $count_value, 5 * MINUTE_IN_SECONDS );
		}
	}

	protected function get_counter() {
		if ( $this->mode == 'preview' ) {
			return false;
		} else {
			return get_transient( $this->generate_key() );
		}
	}

	public function make_header() {
		do_action( 'woe_make_header_custom_formatter', $this->labels );

		return '';
	}

	protected static function get_array_from_array( $array, $key ) {
		return isset( $array[ $key ] ) && is_array( $array[ $key ] ) ? $array[ $key ] : array();
	}
	
	//for plain formats only 
	public function adjust_duplicated_fields_settings( $order_ids, $make_mode = '', $settings = array() ){
	}

	public function get_duplicate_settings() {
		return array();
	}
}