<?php
/**
 * MLO Attachment DB Renamer class.
 *
 * URL Replacer for WordPress — replaces URLs in the database,
 * including handling image size variations and scaled images.
 *
 * @package Media_Library_Organizer
 */

/**
 * Media_Library_Organizer_Renamer
 */
class Media_Library_Organizer_Renamer {
	/**
	 * Tables to skip during replacement
	 *
	 * @var array
	 */
	private $skip_tables = array();

	/**
	 * Columns to skip during replacement
	 *
	 * @var array
	 */
	private $skip_columns = array( 'user_pass' );

	/**
	 * Handle image size variations
	 *
	 * @var bool
	 */
	private $handle_image_sizes = false;

	/**
	 * Constructor
	 *
	 * @param bool $skip_sizes Whether to skip image size handling.
	 */
	public function __construct( $skip_sizes = false ) {
		global $wpdb;

		$this->handle_image_sizes = ! $skip_sizes;

		// Initialize skip tables with properly prefixed names.
		$this->skip_tables = array(
			$wpdb->users,
			$wpdb->terms,
			$wpdb->term_relationships,
			$wpdb->term_taxonomy,
		);
	}

	/**
	 * Replace URLs in the WordPress database
	 *
	 * @param string $old_url The base URL to search for.
	 * @param string $new_url The base URL to replace with.
	 *
	 * @return int Number of replacements made
	 */
	public function replace( $old_url, $new_url ) {
		if ( $old_url === $new_url ) {
			return 0;
		}

		if ( empty( $old_url ) || empty( $new_url ) ) {
			return 0;
		}

		$tables             = $this->get_tables();
		$total_replacements = 0;

		foreach ( $tables as $table ) {
			if ( in_array( $table, $this->skip_tables, true ) ) {
				continue;
			}

			list( $primary_keys, $columns ) = $this->get_columns( $table );

			// Skip tables with no primary keys.
			if ( empty( $primary_keys ) ) {
				continue;
			}

			foreach ( $columns as $column ) {
				if ( in_array( $column, $this->skip_columns, true ) ) {
					continue;
				}

				$replacements        = $this->process_column( $table, $column, $primary_keys, $old_url, $new_url );
				$total_replacements += $replacements;
			}
		}

		return $total_replacements;
	}

	/**
	 * Get WordPress tables
	 *
	 * @return array Table names
	 */
	private function get_tables() {
		global $wpdb;

		return array_values( $wpdb->tables() );
	}

	/**
	 * Get columns for a table
	 *
	 * @param string $table Table name.
	 *
	 * @return array Array containing primary keys and text columns
	 */
	private function get_columns( $table ) {
		global $wpdb;

		$primary_keys = array();
		$text_columns = array();

		// Validate and escape table name.
		$escaped_table = $this->escape_identifier( $table );
		if ( false === $escaped_table ) {
			return array( array(), array() );
		}

		$results = $wpdb->get_results( "DESCRIBE {$escaped_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! empty( $results ) ) {
			// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			foreach ( $results as $col ) {
				if ( 'PRI' === $col->Key ) {
					$primary_keys[] = $col->Field;
				}
				if ( $this->is_text_col( $col->Type ) ) {
					$text_columns[] = $col->Field;
				}
			}
			// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		}

		return array( $primary_keys, $text_columns );
	}

	/**
	 * Check if column is text type
	 *
	 * @param string $type Column type.
	 *
	 * @return bool True if text column
	 */
	private function is_text_col( $type ) {
		foreach ( array( 'text', 'varchar', 'longtext', 'mediumtext', 'char' ) as $token ) {
			if ( false !== stripos( $type, $token ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Validate and escape a SQL identifier (table or column name)
	 *
	 * @param string $identifier The identifier to validate and escape.
	 *
	 * @return string|false Escaped identifier or false if invalid
	 */
	private function escape_identifier( $identifier ) {
		// Identifiers must contain only alphanumeric characters and underscores.
		if ( ! preg_match( '/^[a-zA-Z0-9_]+$/', $identifier ) ) {
			return false;
		}

		// Escape with backticks for safe use in SQL.
		return '`' . esc_sql( $identifier ) . '`';
	}

	/**
	 * Process a single column for replacements
	 *
	 * @param string $table        Table name.
	 * @param string $column       Column name.
	 * @param array  $primary_keys Primary keys.
	 * @param string $old_url      Old URL.
	 * @param string $new_url      New URL.
	 *
	 * @return int Number of replacements
	 */
	private function process_column( $table, $column, $primary_keys, $old_url, $new_url ) {
		global $wpdb;

		$count = 0;

		// Validate and escape table and column names.
		$escaped_table  = $this->escape_identifier( $table );
		$escaped_column = $this->escape_identifier( $column );

		if ( false === $escaped_table || false === $escaped_column ) {
			return 0;
		}

		// Check for serialized data using validated identifiers.
		$has_serialized = $wpdb->get_var(
			"SELECT COUNT({$escaped_column}) FROM {$escaped_table} WHERE {$escaped_column} REGEXP '^[aiO]:[1-9]' LIMIT 1" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		if ( $has_serialized ) {
			$count = $this->php_handle_column( $table, $column, $primary_keys, $old_url, $new_url );
		} else {
			$count = $this->sql_handle_column( $table, $column, $primary_keys, $old_url, $new_url );
		}

		return $count;
	}

	/**
	 * Handle column using SQL replacement
	 *
	 * @param string $table        Table name.
	 * @param string $column       Column name.
	 * @param array  $primary_keys Primary keys.
	 * @param string $old_url      Old URL.
	 * @param string $new_url      New URL.
	 *
	 * @return int Number of replacements
	 */
	private function sql_handle_column( $table, $column, $primary_keys, $old_url, $new_url ) {
		global $wpdb;
		$count = 0;

		// Validate and escape table and column names.
		$escaped_table  = $this->escape_identifier( $table );
		$escaped_column = $this->escape_identifier( $column );

		if ( false === $escaped_table || false === $escaped_column ) {
			return 0;
		}

		$old_path_parts = parse_url( $old_url ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
		if ( ! isset( $old_path_parts['path'] ) ) {
			return 0;
		}

		$old_path      = $old_path_parts['path'];
		$old_file_info = pathinfo( $old_path );

		$old_base   = $old_file_info['filename'];
		$old_dir    = dirname( $old_path );
		$old_domain = isset( $old_path_parts['host'] ) ? 'http' . ( isset( $old_path_parts['scheme'] ) && $old_path_parts['scheme'] === 'https' ? 's' : '' ) . '://' . $old_path_parts['host'] : '';

		$base_url = $old_domain . $old_dir . '/' . $old_base;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$escaped_table} WHERE {$escaped_column} LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'%' . $wpdb->esc_like( $base_url ) . '%'
			)
		);

		$json_base_url = str_replace( '/', '\\/', $base_url );

		$json_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$escaped_table} WHERE {$escaped_column} LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'%' . $wpdb->esc_like( $json_base_url ) . '%'
			)
		);

		$processed_ids = array();
		$all_rows      = array_merge( $rows, $json_rows );

		if ( empty( $all_rows ) ) {
			return 0;
		}

		foreach ( $all_rows as $row ) {
			$row_id = '';
			foreach ( $primary_keys as $key ) {
				if ( ! isset( $row->$key ) ) {
					continue 2;
				}
				$row_id .= $row->$key . '|';
			}

			if ( isset( $processed_ids[ $row_id ] ) ) {
				continue;
			}
			$processed_ids[ $row_id ] = true;

			$content     = $row->$column;
			$new_content = $this->replace_image_urls( $content, $old_url, $new_url );

			if ( $content !== $new_content ) {
				$where_conditions = array();
				foreach ( $primary_keys as $key ) {
					$where_conditions[ $key ] = $row->$key;
				}

				$wpdb->update(
					$table,
					array( $column => $new_content ),
					$where_conditions
				);
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Handle column using PHP for serialized data
	 *
	 * @param string $table        Table name.
	 * @param string $column       Column name.
	 * @param array  $primary_keys Primary keys.
	 * @param string $old_url      Old URL.
	 * @param string $new_url      New URL.
	 *
	 * @return int Number of replacements
	 */
	private function php_handle_column( $table, $column, $primary_keys, $old_url, $new_url ) {
		global $wpdb;

		$count        = 0;
		$json_old_url = str_replace( '/', '\\/', $old_url );

		// Validate and escape table and column names.
		$escaped_table  = $this->escape_identifier( $table );
		$escaped_column = $this->escape_identifier( $column );

		if ( false === $escaped_table || false === $escaped_column ) {
			return 0;
		}

		// Build the SELECT clause with validated and escaped primary key columns.
		$escaped_primary_keys = array();
		foreach ( $primary_keys as $key ) {
			$escaped_key = $this->escape_identifier( $key );
			if ( false === $escaped_key ) {
				return 0;
			}
			$escaped_primary_keys[] = $escaped_key;
		}
		$select_fields = implode( ', ', $escaped_primary_keys ) . ', ' . $escaped_column;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT {$select_fields} FROM {$escaped_table} WHERE {$escaped_column} LIKE %s LIMIT 100", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'%' . $wpdb->esc_like( $old_url ) . '%'
			)
		);

		$json_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT {$select_fields} FROM {$escaped_table} WHERE {$escaped_column} LIKE %s LIMIT 100", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'%' . $wpdb->esc_like( $json_old_url ) . '%'
			)
		);

		$processed_ids = array();
		$all_rows      = array_merge( $rows, $json_rows );

		foreach ( $all_rows as $row ) {
			$row_id = '';
			foreach ( $primary_keys as $key ) {
				$row_id .= $row->$key . '|';
			}

			if ( isset( $processed_ids[ $row_id ] ) ) {
				continue;
			}

			$processed_ids[ $row_id ] = true;
			$value                    = $row->$column;

			if ( empty( $value ) ) {
				continue;
			}

			$new_value = $this->replace_urls_in_value( $value, $old_url, $new_url );

			if ( $value === $new_value ) {
				continue;
			}

			$where_conditions = array();
			foreach ( $primary_keys as $key ) {
				$where_conditions[ $key ] = $row->$key;
			}

			$updated = $wpdb->update(
				$table,
				array( $column => $new_value ),
				$where_conditions
			);

			if ( $updated ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Replace URLs in a value, handling serialized data
	 *
	 * @param string $value   The value to process.
	 * @param string $old_url Old URL.
	 * @param string $new_url New URL.
	 *
	 * @return string The processed value
	 */
	private function replace_urls_in_value( $value, $old_url, $new_url ) {
		if ( $this->is_serialized( $value ) ) {
			$unserialized = unserialize( $value, array( 'allowed_classes' => false ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize

			if ( false !== $unserialized || 'b:0;' === $value ) {
				$replaced = $this->replace_in_data( $unserialized, $old_url, $new_url );
				return serialize( $replaced ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
			}
		}

		if ( $this->handle_image_sizes ) {
			return $this->replace_image_urls( $value, $old_url, $new_url );
		}

		return str_replace( $old_url, $new_url, $value );
	}

	/**
	 * Replace image URLs including various WordPress size variations and scaled images
	 *
	 * @param string $content The content to process.
	 * @param string $old_url Old URL pattern.
	 * @param string $new_url New URL pattern.
	 *
	 * @return string The processed content
	 */
	private function replace_image_urls( $content, $old_url, $new_url ) {
		$old_path_parts = parse_url( $old_url ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
		$new_path_parts = parse_url( $new_url ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url

		if ( ! isset( $old_path_parts['path'] ) || ! isset( $new_path_parts['path'] ) ) {
			return str_replace( $old_url, $new_url, $content );
		}

		$old_path = $old_path_parts['path'];
		$new_path = $new_path_parts['path'];

		$old_file_info = pathinfo( $old_path );
		$new_file_info = pathinfo( $new_path );

		$old_base = $old_file_info['filename'];
		$new_base = $new_file_info['filename'];
		$old_ext  = isset( $old_file_info['extension'] ) ? $old_file_info['extension'] : '';
		$new_ext  = isset( $new_file_info['extension'] ) ? $new_file_info['extension'] : $old_ext;

		$old_domain = isset( $old_path_parts['host'] ) ? 'http' . ( isset( $old_path_parts['scheme'] ) && $old_path_parts['scheme'] === 'https' ? 's' : '' ) . '://' . $old_path_parts['host'] : '';
		$new_domain = isset( $new_path_parts['host'] ) ? 'http' . ( isset( $new_path_parts['scheme'] ) && $new_path_parts['scheme'] === 'https' ? 's' : '' ) . '://' . $new_path_parts['host'] : '';

		// Replace original URLs.
		$content = str_replace( $old_url, $new_url, $content );

		// Replace JSON-escaped URLs.
		$json_old_url = str_replace( '/', '\\/', $old_url );
		$json_new_url = str_replace( '/', '\\/', $new_url );
		$content      = str_replace( $json_old_url, $json_new_url, $content );

		if ( ! empty( $old_ext ) && $this->handle_image_sizes ) {
			$old_dir = dirname( $old_path );
			$new_dir = dirname( $new_path );

			// Replace WordPress image size variations (e.g., image-300x200.jpg).
			$size_pattern = '/' . preg_quote( $old_domain . $old_dir . '/' . $old_base, '/' ) . '-\d+x\d+\.' . preg_quote( $old_ext, '/' ) . '/';

			$content = preg_replace_callback(
				$size_pattern,
				function ( $matches ) use ( $old_base, $new_base, $old_domain, $new_domain, $old_dir, $new_dir, $old_ext, $new_ext ) {
					$size_part = substr( $matches[0], strlen( $old_domain . $old_dir . '/' . $old_base ), -strlen( '.' . $old_ext ) );
					return $new_domain . $new_dir . '/' . $new_base . $size_part . '.' . $new_ext;
				},
				$content
			);

			// Replace -scaled variations.
			$scaled_pattern = '/' . preg_quote( $old_domain . $old_dir . '/' . $old_base, '/' ) . '-scaled\.' . preg_quote( $old_ext, '/' ) . '/';

			$content = preg_replace_callback(
				$scaled_pattern,
				function () use ( $new_base, $new_domain, $new_dir, $new_ext ) {
					return $new_domain . $new_dir . '/' . $new_base . '-scaled.' . $new_ext;
				},
				$content
			);

			// Replace JSON-escaped variations.
			$json_size_pattern = '/' . preg_quote( str_replace( '/', '\\/', $old_domain . $old_dir . '/' . $old_base ), '/' ) . '-\d+x\d+\.' . preg_quote( $old_ext, '/' ) . '/';

			$content = preg_replace_callback(
				$json_size_pattern,
				function ( $matches ) use ( $old_base, $new_base, $old_domain, $new_domain, $old_dir, $new_dir, $old_ext, $new_ext ) {
					$size_part = substr( $matches[0], strlen( str_replace( '/', '\\/', $old_domain . $old_dir . '/' . $old_base ) ), -strlen( '.' . $old_ext ) );
					return str_replace( '/', '\\/', $new_domain . $new_dir . '/' . $new_base . $size_part . '.' . $new_ext );
				},
				$content
			);

			// Replace JSON-escaped scaled variations.
			$json_scaled_pattern = '/' . preg_quote( str_replace( '/', '\\/', $old_domain . $old_dir . '/' . $old_base ), '/' ) . '-scaled\.' . preg_quote( $old_ext, '/' ) . '/';

			$content = preg_replace_callback(
				$json_scaled_pattern,
				function () use ( $new_base, $new_domain, $new_dir, $new_ext ) {
					return str_replace( '/', '\\/', $new_domain . $new_dir . '/' . $new_base . '-scaled.' . $new_ext );
				},
				$content
			);
		}

		return $content;
	}

	/**
	 * Recursively replace URLs in data structure
	 *
	 * @param mixed  $data    The data to process.
	 * @param string $old_url Old URL.
	 * @param string $new_url New URL.
	 *
	 * @return mixed The processed data
	 */
	private function replace_in_data( $data, $old_url, $new_url ) {
		if ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				$data[ $key ] = $this->replace_in_data( $value, $old_url, $new_url );
			}
		} elseif ( is_object( $data ) ) {
			foreach ( $data as $key => $value ) {
				$data->$key = $this->replace_in_data( $value, $old_url, $new_url );
			}
		} elseif ( is_string( $data ) ) {
			if ( $this->handle_image_sizes ) {
				$data = $this->replace_image_urls( $data, $old_url, $new_url );
			} else {
				$data = str_replace( $old_url, $new_url, $data );
			}
		}

		return $data;
	}

	/**
	 * Check if a string is serialized
	 *
	 * @param string|mixed $data String to check.
	 *
	 * @return bool True if serialized
	 */
	private function is_serialized( $data ) {
		if ( ! is_string( $data ) ) {
			return false;
		}

		$data = trim( $data );
		if ( 'N;' === $data ) {
			return true;
		}

		if ( strlen( $data ) < 4 ) {
			return false;
		}

		if ( ':' !== $data[1] ) {
			return false;
		}

		$last_char = substr( $data, -1 );
		if ( ';' !== $last_char && '}' !== $last_char ) {
			return false;
		}

		$token = $data[0];
		switch ( $token ) {
			case 's':
				if ( '"' !== substr( $data, -2, 1 ) ) {
					return false;
				}
				// Fall through.
			case 'a':
			case 'O':
			case 'i':
			case 'd':
				return (bool) preg_match( "/^{$token}:[0-9]+:/", $data );
			default:
				return false;
		}
	}
}
