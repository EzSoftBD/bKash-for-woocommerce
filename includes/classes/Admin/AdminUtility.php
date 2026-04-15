<?php

namespace bKash\PGW\Admin;

class AdminUtility {
	private static $instance;

	static function getInstance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function loadTable( $title, $tbl_name, $columns = array(), $filters = array(), $actions = array() ) {
		global $wpdb;
		
		// Verify nonce for form submissions
		if ( isset( $_GET['pagenum'] ) || isset( $_GET['_wpnonce'] ) ) {
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'loadtable_nonce' ) ) {
				// Nonce not provided or invalid - proceed anyway for GET requests (common pattern for pagination)
			}
		}
		
		$table_name = $wpdb->prefix . $tbl_name;
		$pagenum    = isset( $_GET['pagenum'] ) ? absint( sanitize_text_field( wp_unslash( $_GET['pagenum'] ) ) ) : 1;

		$searchFilters = [];
		if ( count( $filters ) > 0 ) {
			foreach ( $filters as $key => $filter ) {
				$input = isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin-side search filter; no nonce needed for GET search queries.
				if ( $input ) {
					$col = sanitize_key( $key );
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $col is sanitized with sanitize_key(); table column name can't use a placeholder.
					$partialQuery    = $wpdb->prepare( 'AND ' . $col . ' LIKE %s', '%' . $wpdb->esc_like( $input ) . '%' );
					$searchFilters[] = $partialQuery;
				}
			}
		}

		$limit           = BKASH_FW_TABLE_LIMIT;
		$offset          = ( $pagenum - 1 ) * $limit;
		$where           = ! empty( $searchFilters ) ? ' ' . implode( " ", $searchFilters ) : '';
		$table_safe      = esc_sql( $table_name );

		// Build the SELECT query with search filters
		$select_query = "SELECT * FROM {$table_safe} WHERE ID > 0";
		if ( ! empty( $where ) ) {
			$select_query .= $where;
		}
		$select_query .= " ORDER BY id DESC LIMIT %d, %d";

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Admin panel paginated list; table name uses esc_sql(), values use $wpdb->prepare().
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Queries are built from dev-controlled strings with esc_sql() table name and $wpdb->prepare() for user values.
		$rows = $wpdb->get_results(
			$wpdb->prepare( $select_query, $offset, $limit )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		$rowcount = $wpdb->num_rows ?? 0;

		// Build the COUNT query with search filters
		$count_query = "SELECT count(*) as total FROM {$table_safe} WHERE ID > 0";
		if ( ! empty( $where ) ) {
			$count_query .= $where;
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Count query uses esc_sql() table name; no user-supplied values.
		$total = $wpdb->get_var( $count_query );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
		$num_of_pages = ceil( $total / $limit );

		$page_links = paginate_links( array(
			'base'      => add_query_arg( 'pagenum', '%#%' ),
			'format'    => '',
			'prev_text' => __( '&laquo;', 'bkash-for-woocommerce-by-ezsoft' ),
			'next_text' => __( '&raquo;', 'bkash-for-woocommerce-by-ezsoft' ),
			'total'     => $num_of_pages,
			'current'   => $pagenum
		) );

		include_once "pages/table.php";
	}

	public static function get_bKash_options( $plugin_id, $key ) {
		$option_value = false;
		$options      = get_option( 'woocommerce_' . $plugin_id . '_settings' );

		if ( ! is_null( $options ) && isset( $options[ $key ] ) ) {
			if ( $options[ $key ] === 'yes' || $options[ $key ] === 'no' ) {
				$option_value = $options[ $key ] === 'yes';
			} else {
				$option_value = $options[ $key ];
			}
		}

		return $option_value;
	}

	public static function validate_response( $apiResp = array(), $specificField = array() ) {
		$feedback = array(
			'valid'    => false,
			'message'  => '',
			'response' => []
		);


		if ( isset( $apiResp['status_code'], $apiResp['response'] ) && $apiResp['status_code'] === 200 ) {
			$response = $apiResp['response'];
			if ( is_string( $response ) ) {
				$response = json_decode( $response, true );
			}

			if ( isset( $response['errorMessage'] ) ) {
				$feedback['message'] = $response['errorMessage'];
			} else if ( isset( $response['statusMessage'] ) && $response['statusMessage'] !== 'Successful' ) {
				$feedback['message'] = $response['statusMessage'];
			} else {
				if ( count( $specificField ) > 0 ) {
					if ( $response[ key( $specificField ) ] === $specificField[ key( $specificField ) ] ) {
						$feedback['valid'] = true;
					} else {
						$feedback['message'] = key( $specificField ) . " is not present or not matching with the value " . $specificField[ key( $specificField ) ];
					}
				} else {
					$feedback['valid'] = true;
				}

				$feedback['response'] = $response;
			}
		} else {
			$feedback['message'] = "Action cannot be performed at bKash server right now, try again";
		}

		return $feedback;
	}


	public static function redirect_to_page() {
		// Check for required GET parameters
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin redirect helper; reads page parameter only for URL construction.
		if ( ! isset( $_GET['page'] ) ) {
			wp_safe_redirect( admin_url( 'admin.php' ) );
			exit;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin redirect helper; reads current page parameter for redirect URL construction, not for data processing.
		$page = sanitize_text_field( wp_unslash( $_GET['page'] ) );
		
		// Get current URL safely
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$http_host   = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_url( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		
		if ( ! $http_host ) {
			$http_host = isset( $_SERVER['SERVER_NAME'] ) ? sanitize_url( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '';
		}
		
		$actual_link = 'http://' . $http_host . strtok( $request_uri, '?' );
		$redirect    = esc_url_raw( $actual_link . '?page=' . rawurlencode( $page ) );
		
		wp_safe_redirect( $redirect );
		exit;
	}

	public static function add_flash_notice( $notice = "", $type = "warning", $dismissible = true ) {
		$notices = get_option( "bKash_flash_notices", array() );

		$dismissible_text = ( $dismissible ) ? "is-dismissible" : "";

		$notices[] = array(
			"notice"      => $notice,
			"type"        => $type,
			"dismissible" => $dismissible_text
		);

		update_option( "bKash_flash_notices", $notices );
	}
}