<?php
/**
 * Import class.
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

/**
 * Handles importing settings from this Plugin, and other Plugins, into
 * this Plugin.
 *
 * @since   1.0.0
 */
class Media_Library_Organizer_Import {

	/**
	 * The Cron hook that processes queued Import batches.
	 *
	 * @var     string
	 */
	const CRON_HOOK = 'media_library_organizer_process_import';

	/**
	 * Option storing the Import's progress.
	 *
	 * @var     string
	 */
	const STATE_OPTION = 'media-library-organizer-import';

	/**
	 * Option storing the Folder IDs to import, ordered parents first.
	 *
	 * @var     string
	 */
	const QUEUE_OPTION = 'media-library-organizer-import-queue';

	/**
	 * Option storing third party Folder ID to Media Library Organizer Term ID mappings.
	 *
	 * @var     string
	 */
	const MAPPINGS_OPTION = 'media-library-organizer-import-mappings';

	/**
	 * Option storing the timestamp of the request currently processing a batch.
	 *
	 * @var     string
	 */
	const LOCK_OPTION = 'media-library-organizer-import-lock';

	/**
	 * Folders are stored in the third party Plugin's own database tables.
	 *
	 * @var     string
	 */
	const STORAGE_TABLE = 'table';

	/**
	 * Folders are stored in a WordPress Taxonomy.
	 *
	 * @var     string
	 */
	const STORAGE_TAXONOMY = 'taxonomy';

	/**
	 * Number of Folders to import per batch.
	 *
	 * @var     int
	 */
	const FOLDER_BATCH_SIZE = 25;

	/**
	 * Number of Attachment to Folder relationships to read per batch.
	 *
	 * @var     int
	 */
	const ATTACHMENT_BATCH_SIZE = 50;

	/**
	 * Number of times a batch may be attempted before it's recorded as failed and skipped.
	 *
	 * @var     int
	 */
	const MAX_BATCH_ATTEMPTS = 3;

	/**
	 * Maximum number of errors to store, to prevent the progress Option growing unbounded.
	 *
	 * @var     int
	 */
	const MAX_ERRORS = 50;

	/**
	 * Number of seconds after which a lock held by a dead request is considered stale.
	 *
	 * @var     int
	 */
	const LOCK_TIMEOUT = 300;

	/**
	 * Holds the base class object.
	 *
	 * @since   1.0.0
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Holds the last error encountered when importing a settings file.
	 *
	 * @var     string
	 */
	public $error_message;

	/**
	 * Whether this request is currently processing a batch.
	 *
	 * @var     bool
	 */
	private $processing = false;

	/**
	 * Whether this request's shutdown handler has been registered.
	 *
	 * @var     bool
	 */
	private $shutdown_registered = false;

	/**
	 * Holds the claim that this request has on the Import lock, if any.
	 *
	 * @var     string|null
	 */
	private $lock_claim = null;

	/**
	 * Holds the base class object.
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

		add_action( self::CRON_HOOK, array( $this, 'process_job' ) );

		// Define Import Sources.
		add_filter( 'media_library_organizer_import_sources', array( $this, 'import_sources' ) );

		// Importers.
		add_action( 'media_library_organizer_import', array( $this, 'import' ), 10, 2 );

		// Third Party Importers.
		add_filter( 'media_library_organizer_import_third_party', array( $this, 'import_third_party' ), 10, 2 );

		// The remaining hooks are only used by the WordPress Administration interface.
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_media_library_organizer_import_start', array( $this, 'ajax_start' ) );
		add_action( 'wp_ajax_media_library_organizer_import_status', array( $this, 'ajax_status' ) );
		add_action( 'wp_ajax_media_library_organizer_import_cancel', array( $this, 'ajax_cancel' ) );
	}

	/**
	 * Helper method to retrieve an array of import sources that this plugin
	 * can import link data from.
	 *
	 * These will typically be other WordPress Plugins that have data stored
	 * in this WordPress installation.
	 *
	 * @since   1.0.0
	 *
	 * @param   array $import_sources     Import Sources.
	 * @return  array                       Import Sources
	 */
	public function import_sources( $import_sources ) {

		// Enhanced Media Library.
		// The Taxonomies to import are chosen by the user, so its availability depends on
		// the Plugin having been installed, rather than on any Terms existing.
		$eml = get_option( 'wpuxss_eml_version' );
		if ( ! empty( $eml ) ) {
			$import_sources['import_enhanced_media_library'] = array(
				'name'          => 'import_enhanced_media_library',
				'label'         => __( 'Import from Enhanced Media Library', 'media-library-organizer' ),
				'view'          => $this->base->plugin->folder . 'views/admin/import-enhanced-media-library.php',
				'data'          => array(
					'taxonomies' => get_option( 'wpuxss_eml_taxonomies' ),
				),
				'documentation' => $this->base->plugin->documentation_url . '/import-export/import-from-enhanced-media-library/',
			);
		}

		// FileBird.
		if ( $this->source_has_data( 'import_filebird' ) ) {
			$import_sources['import_filebird'] = array(
				'name'          => 'import_filebird',
				'label'         => __( 'Import from FileBird', 'media-library-organizer' ),
				'view'          => $this->base->plugin->folder . 'views/admin/import-filebird.php',
				'documentation' => $this->base->plugin->documentation_url . '/import-export/import-from-filebird/',
			);
		}

		// Folders.
		if ( $this->source_has_data( 'import_folders' ) ) {
			$import_sources['import_folders'] = array(
				'name'          => 'import_folders',
				'label'         => __( 'Import from Folders (Premio)', 'media-library-organizer' ),
				'view'          => $this->base->plugin->folder . 'views/admin/import-folders.php',
				'documentation' => $this->base->plugin->documentation_url . '/import-export/import-from-folders-premio/',
			);
		}

		// HappyFiles.
		if ( $this->source_has_data( 'import_happyfiles' ) ) {
			$import_sources['import_happyfiles'] = array(
				'name'          => 'import_happyfiles',
				'label'         => __( 'Import from HappyFiles', 'media-library-organizer' ),
				'view'          => $this->base->plugin->folder . 'views/admin/import-happyfiles.php',
				'documentation' => $this->base->plugin->documentation_url . '/import-export/import-from-happyfiles/',
			);
		}

		// WP Media Folder.
		if ( $this->source_has_data( 'import_wp_media_folder' ) ) {
			$import_sources['import_wp_media_folder'] = array(
				'name'          => 'import_wp_media_folder',
				'label'         => __( 'Import from WP Media Folder', 'media-library-organizer' ),
				'view'          => $this->base->plugin->folder . 'views/admin/import-wp-media-folder.php',
				'documentation' => $this->base->plugin->documentation_url . '/import-export/import-from-wp-media-folder/',
			);
		}

		// Wicked Folders.
		if ( $this->source_has_data( 'import_wicked_folders' ) ) {
			$import_sources['import_wicked_folders'] = array(
				'name'          => 'import_wicked_folders',
				'label'         => __( 'Import from Wicked Folders', 'media-library-organizer' ),
				'view'          => $this->base->plugin->folder . 'views/admin/import-wicked-folders.php',
				'documentation' => $this->base->plugin->documentation_url . '/import-export/import-from-wicked-folders/',
			);
		}

		// Media Library Assistant (David Lingren) — taxonomy: attachment_category.
		if ( $this->source_has_data( 'import_mla' ) ) {
			$import_sources['import_mla'] = array(
				'name'          => 'import_mla',
				'label'         => __( 'Import from Media Library Assistant', 'media-library-organizer' ),
				'view'          => $this->base->plugin->folder . 'views/admin/import-mla.php',
				'documentation' => $this->base->plugin->documentation_url . '/import-export/import-from-media-library-assistant/',
			);
		}

		// Mediamatic (Plugincraft) — taxonomy: mediamatic_wpfolder.
		if ( $this->source_has_data( 'import_mediamatic' ) ) {
			$import_sources['import_mediamatic'] = array(
				'name'          => 'import_mediamatic',
				'label'         => __( 'Import from Mediamatic', 'media-library-organizer' ),
				'view'          => $this->base->plugin->folder . 'views/admin/import-mediamatic.php',
				'documentation' => $this->base->plugin->documentation_url . '/import-export/import-from-mediamatic/',
			);
		}

		// WP Real Media Library (devowl.io) — stores folders in `wp_realmedialibrary` custom table.
		if ( $this->source_has_data( 'import_rml' ) ) {
			$import_sources['import_rml'] = array(
				'name'          => 'import_rml',
				'label'         => __( 'Import from WP Real Media Library', 'media-library-organizer' ),
				'view'          => $this->base->plugin->folder . 'views/admin/import-rml.php',
				'documentation' => $this->base->plugin->documentation_url . '/import-export/import-from-real-media-library/',
			);
		}

		// Return.
		return $import_sources;
	}

	/**
	 * Import data created by this Plugin's export functionality
	 *
	 * @since   1.0.0
	 *
	 * @param   bool  $success    Success.
	 * @param   array $import     Array.
	 */
	public function import( $success, $import ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Bail if no data.
		if ( ! is_array( $import['data'] ) ) {
			$this->error_message = __( 'The uploaded file is not a valid settings file, or it may be damaged. Please export a new copy and try again.', 'media-library-organizer' );
			return;
		}

		// Iterate through settings screens ($data), saving the settings.
		foreach ( $import['data'] as $type => $value ) {
			$this->base->get_class( 'settings' )->update_settings( $type, $value );
		}
	}

	/**
	 * Import data from a third party
	 *
	 * @since   1.1.0
	 *
	 * @param   mixed $success    WP_Error | bool.
	 * @param   array $import     Import Parameters.
	 * @return  mixed             WP_Error | string | bool
	 */
	public function import_third_party( $success, $import ) {

		$sources = $this->get_sources();

		foreach ( $sources as $name => $source ) {
			// Skip if this Import Source's button wasn't the one submitted.
			if ( ! isset( $import[ $name ] ) ) {
				continue;
			}

			$args = array();

			if ( ! empty( $source['choose_taxonomies'] ) ) {
				if ( empty( $import['taxonomies'] ) ) {
					return new WP_Error(
						'media_library_organizer_import_taxonomies_required',
						$source['label'] . ': ' . __( 'Please select at least one Taxonomy to import.', 'media-library-organizer' )
					);
				}

				$args['taxonomies'] = (array) $import['taxonomies'];
			}

			$state = $this->start( $name, $args );

			if ( is_wp_error( $state ) ) {
				return $state;
			}

			return __( 'The import has started, and will continue in the background until it completes.', 'media-library-organizer' );
		}

		return $success;
	}

	/**
	 * Returns an array of Import Sources that this Plugin can import from.
	 *
	 * @return  array   Import Sources
	 */
	public function get_sources() {

		global $wpdb;

		$sources = array(
			'import_enhanced_media_library' => array(
				'label'             => __( 'Import from Enhanced Media Library', 'media-library-organizer' ),
				// Chosen by the user on the Import screen, rather than fixed by this Source.
				'taxonomies'        => array(),
				'choose_taxonomies' => true,
			),
			'import_filebird'               => array(
				'label'      => __( 'Import from FileBird', 'media-library-organizer' ),
				// FileBird v5+ stores Folders in the `fbv` table; older versions used a Taxonomy.
				'table'      => array(
					'folders'          => $wpdb->prefix . 'fbv',
					'folders_id'       => 'id',
					'folders_name'     => 'name',
					'folders_parent'   => 'parent',
					'folders_where'    => 'type = 0',
					'folders_orderby'  => 'parent ASC, ord ASC',
					'relations'        => $wpdb->prefix . 'fbv_attachment_folder',
					'relations_folder' => 'folder_id',
					'relations_object' => 'attachment_id',
				),
				'taxonomies' => array( 'nt_wmc_folder' ),
			),
			'import_folders'                => array(
				'label'      => __( 'Import from Folders (Premio)', 'media-library-organizer' ),
				'taxonomies' => array( 'media_folder' ),
			),
			'import_happyfiles'             => array(
				'label'      => __( 'Import from HappyFiles', 'media-library-organizer' ),
				'taxonomies' => array( 'happyfiles_category' ),
			),
			'import_wicked_folders'         => array(
				'label'      => __( 'Import from Wicked Folders', 'media-library-organizer' ),
				'taxonomies' => array( 'wf_attachment_folders' ),
			),
			'import_wp_media_folder'        => array(
				'label'      => __( 'Import from WP Media Folder', 'media-library-organizer' ),
				'taxonomies' => array( 'wpmf-category' ),
			),
			'import_mla'                    => array(
				'label'      => __( 'Import from Media Library Assistant', 'media-library-organizer' ),
				'taxonomies' => array( 'attachment_category' ),
			),
			'import_mediamatic'             => array(
				'label'      => __( 'Import from Mediamatic', 'media-library-organizer' ),
				'taxonomies' => array( 'mediamatic_wpfolder' ),
			),
			'import_rml'                    => array(
				'label' => __( 'Import from WP Real Media Library', 'media-library-organizer' ),
				// Only type = 0 rows are regular Folders, rather than collections or galleries.
				// The virtual root Folder is -1, rather than 0.
				'table' => array(
					'folders'          => $wpdb->prefix . 'realmedialibrary',
					'folders_id'       => 'id',
					'folders_name'     => 'name',
					'folders_parent'   => 'parent',
					'folders_where'    => 'type = 0',
					'folders_orderby'  => 'parent ASC, ord ASC',
					'relations'        => $wpdb->prefix . 'realmedialibrary_posts',
					'relations_folder' => 'fid',
					'relations_object' => 'attachment',
				),
			),
		);

		/**
		 * Filters the Import Sources that this Plugin can import from.
		 *
		 * @param   array   $sources    Import Sources.
		 * @return  array               Import Sources
		 */
		return apply_filters( 'media_library_organizer_import_background_sources', $sources );
	}

	/**
	 * Whether the given Import Source has any Folders to import.
	 *
	 * @param   string $name   Import Source name.
	 * @return  bool
	 */
	public function source_has_data( $name ) {

		return ! is_wp_error( $this->resolve_source( $name ) );
	}

	/**
	 * Returns the Import's progress, merged over its defaults.
	 *
	 * @return  array
	 */
	public function get_state() {

		$state = get_option( self::STATE_OPTION, array() );

		if ( ! is_array( $state ) ) {
			$state = array();
		}

		return array_merge( $this->get_default_state(), $state );
	}

	/**
	 * Queues an Import of the given Source, returning an error if one is already running.
	 *
	 * @param   string $name   Import Source name.
	 * @param   array  $args   Optional Import arguments, such as the Taxonomies to import.
	 * @return  WP_Error|array      WP_Error | Import progress
	 */
	public function start( $name, $args = array() ) {

		if ( ! $this->acquire_lock() ) {
			return new WP_Error(
				'media_library_organizer_import_running',
				__( 'An import is already running. Wait for it to finish, or cancel it, before starting another.', 'media-library-organizer' )
			);
		}

		// Only ever allow one Import to run at a time, so that Folders and Attachment
		// assignments can't be processed twice concurrently.
		$state = $this->get_state();
		if ( $this->is_running_state( $state ) ) {
			$this->release_lock();

			return new WP_Error(
				'media_library_organizer_import_running',
				__( 'An import is already running. Wait for it to finish, or cancel it, before starting another.', 'media-library-organizer' )
			);
		}

		$source = $this->resolve_source( $name, $args );
		if ( is_wp_error( $source ) ) {
			$this->release_lock();

			return $source;
		}

		$folders = $this->query_folders( $source );
		if ( empty( $folders ) ) {
			$this->release_lock();

			return new WP_Error(
				'media_library_organizer_import',
				__( 'No terms were imported. The source may be empty or incompatible.', 'media-library-organizer' )
			);
		}

		// Order the Folders so that parents are always imported before their children. Each
		// batch can then rely on its Folders' parents already having been mapped.
		$queue = $this->order_folders_by_hierarchy( $folders );

		update_option( self::QUEUE_OPTION, $queue, false );
		update_option( self::MAPPINGS_OPTION, array(), false );

		$state                      = $this->get_default_state();
		$state['status']            = 'queued';
		$state['stage']             = 'folders';
		$state['source']            = $source['name'];
		$state['label']             = $source['label'];
		$state['storage']           = $source['storage'];
		$state['table']             = $source['table'];
		$state['taxonomies']        = $source['taxonomies'];
		$state['started_at']        = time();
		$state['folders_total']     = count( $queue );
		$state['attachments_total'] = $this->count_attachments( $source, $queue );

		$state = $this->save_state( $state );

		// Queue the first request to process a batch. If it can't be queued, the Import will
		// continue while this screen is open, and resume when it's reopened.
		if ( ! $this->schedule_next() ) {
			$this->reset();

			return new WP_Error(
				'media_library_organizer_import_not_scheduled',
				__( 'The import could not be started, because a background task could not be scheduled on this site. Check whether WP-Cron is disabled, then try again.', 'media-library-organizer' )
			);
		}

		$this->release_lock();

		return $state;
	}

	/**
	 * Stops a queued or running Import.
	 *
	 * @return  array   Import progress
	 */
	public function cancel() {

		$state           = $this->get_state();
		$state['status'] = 'cancelled';
		$state           = $this->save_state( $state );

		$this->unschedule();
		return $state;
	}

	/**
	 * Removes all Import progress and any queued Cron event.
	 */
	public function reset() {

		delete_option( self::STATE_OPTION );
		delete_option( self::QUEUE_OPTION );
		delete_option( self::MAPPINGS_OPTION );
		delete_option( self::LOCK_OPTION );
		$this->lock_claim = null;

		$this->unschedule();
	}

	/**
	 * Processes a batch of Folders or Attachment assignments.
	 *
	 * @return  void
	 */
	public function process_job() {

		$this->process();
	}

	/**
	 * Processes a batch of Folders or Attachment assignments,
	 * and queues the next request if there's still work to do.
	 *
	 * @return  array Import progress
	 */
	public function process() {

		$state = $this->get_state();

		// Bail if there's nothing to do.
		if ( ! $this->is_running_state( $state ) ) {
			return $state;
		}

		// Bail if another request is already processing a batch.
		if ( ! $this->acquire_lock() ) {
			return $state;
		}

		// Make sure that a fatal error leaves the Import in a resumable state.
		$this->processing = true;

		if ( ! $this->shutdown_registered ) {
			register_shutdown_function( array( $this, 'shutdown' ) );
			$this->shutdown_registered = true;
		}

		$state['status'] = 'processing';
		$state           = $this->save_state( $state );

		$state = $this->process_batch( $state );

		$this->processing = false;

		// Queue the next request if there's still work to do.
		if ( 'processing' === $state['status'] ) {
			$state['status'] = 'queued';

			if ( ! $this->schedule_next() ) {
				$state['errors'] = $this->add_error(
					$state['errors'],
					__( 'The import could not be started, because a background task could not be scheduled on this site. Check whether WP-Cron is disabled, then try again.', 'media-library-organizer' )
				);
			}

			$state = $this->save_state( $state );
		}

		$this->release_lock();

		return $state;
	}

	/**
	 * Runs after a fatal error or timeout, leaving the Import in a resumable state so that
	 * the next run picks up where this request stopped.
	 */
	public function shutdown() {

		// Nothing to do if this request finished processing normally.
		if ( ! $this->processing ) {
			return;
		}

		$this->processing = false;

		$state = $this->get_state();
		$error = error_get_last();

		if ( is_array( $error ) && in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR, E_USER_ERROR ), true ) ) {
			$state['errors'] = $this->add_error(
				$state['errors'],
				sprintf(
					/* translators: %s: PHP error message that interrupted the import */
					__( 'The import was interrupted and will resume automatically: %s', 'media-library-organizer' ),
					$error['message']
				)
			);
		}

		if ( $this->is_running_state( $state ) ) {
			$state['status'] = 'queued';

			if ( ! $this->schedule_next() ) {
				$state['errors'] = $this->add_error(
					$state['errors'],
					__( 'The import could not be started, because a background task could not be scheduled on this site. Check whether WP-Cron is disabled, then try again.', 'media-library-organizer' )
				);
			}
		}

		$this->save_state( $state );
		$this->release_lock();
	}

	/**
	 * Processes a single batch of Folders or Attachment assignments.
	 *
	 * @param   array $state    Import progress.
	 * @return  array               Import progress
	 */
	private function process_batch( $state ) {

		$key      = $this->get_batch_key( $state );
		$attempts = isset( $state['batch_attempts'][ $key ] ) ? (int) $state['batch_attempts'][ $key ] : 0;

		// If a batch fails repeatedly, skip it and continue with the next batch.
		if ( $attempts >= self::MAX_BATCH_ATTEMPTS ) {
			return $this->skip_batch( $state, $key );
		}

		// Increment the number of attempts for this batch, so that if it fails again it can be skipped.
		$state['batch_attempts'][ $key ] = $attempts + 1;
		$state                           = $this->save_state( $state );

		switch ( $state['stage'] ) {
			case 'folders':
				$state = $this->process_folders_batch( $state );
				break;

			case 'attachments':
				$state = $this->process_attachments_batch( $state );
				break;

			default:
				$state['status'] = 'complete';
				break;
		}

		// The batch completed, so it no longer needs tracking.
		unset( $state['batch_attempts'][ $key ] );

		return $this->save_state( $state );
	}

	/**
	 * Imports the next batch of Folders as Media Library Organizer Terms.
	 *
	 * @param   array $state    Import progress.
	 * @return  array               Import progress
	 */
	private function process_folders_batch( $state ) {

		$queue = $this->get_queue();
		$total = count( $queue );

		$folder_ids = array_slice( $queue, (int) $state['folder_cursor'], self::FOLDER_BATCH_SIZE );

		// No Folders left; start assigning Attachments.
		if ( empty( $folder_ids ) ) {
			return $this->start_attachments_stage( $state );
		}

		$folders  = $this->query_folders( $state, $folder_ids );
		$mappings = $this->get_mappings();
		$cursor   = (int) $state['folder_cursor'];

		foreach ( $folder_ids as $folder_id ) {
			++$cursor;

			if ( isset( $mappings[ $folder_id ] ) ) {
				continue;
			}

			if ( ! isset( $folders[ $folder_id ] ) || empty( $folders[ $folder_id ]->name ) ) {
				continue;
			}

			$folder = $folders[ $folder_id ];
			$parent = (int) $folder->parent;

			$result = $this->create_term(
				$folder->name,
				$folder->description,
				( $parent > 0 && isset( $mappings[ $parent ] ) ? $mappings[ $parent ] : 0 )
			);

			if ( is_wp_error( $result ) ) {
				$state['errors'] = $this->add_error(
					$state['errors'],
					sprintf(
						/* translators: %1$s: Term name to create, %2$s: Error message from attempting to create term */
						__( 'Term Name: %1$s, Error: %2$s', 'media-library-organizer' ),
						$folder->name,
						$result->get_error_message()
					)
				);
				continue;
			}

			$mappings[ $folder_id ] = (int) $result;
		}

		$this->save_mappings( $mappings );

		$state['folder_cursor'] = $cursor;

		if ( $cursor >= $total ) {
			$state = $this->start_attachments_stage( $state );
		}

		return $state;
	}

	/**
	 * Imports the next batch of Attachment to Folder relationships.
	 *
	 * @param   array $state Import progress.
	 * @return  array        Import progress
	 */
	private function process_attachments_batch( $state ) {

		$rows = $this->get_attachment_rows( $state );

		// No relationships left to process, so the Import is finished.
		if ( empty( $rows ) ) {
			$state['stage']  = 'done';
			$state['status'] = 'complete';
			return $state;
		}

		$mappings = $this->get_mappings();

		$attachments = array();
		foreach ( $rows as $row ) {
			$attachments[ (int) $row->attachment_id ][] = (int) $row->folder_id;
		}

		$existing = $this->get_existing_attachment_ids( array_keys( $attachments ) );

		foreach ( $attachments as $attachment_id => $folder_ids ) {
			$state['attachment_cursor'] = $attachment_id;
			++$state['attachments_processed'];

			if ( ! isset( $existing[ $attachment_id ] ) ) {
				++$state['attachments_missing'];
				continue;
			}

			$term_ids = array();
			foreach ( $folder_ids as $folder_id ) {
				if ( isset( $mappings[ $folder_id ] ) ) {
					$term_ids[] = absint( $mappings[ $folder_id ] );
				}
			}

			if ( empty( $term_ids ) ) {
				continue;
			}

			$result = wp_set_object_terms( $attachment_id, array_values( array_unique( $term_ids ) ), 'mlo-category', false );

			if ( is_wp_error( $result ) ) {
				$state['errors'] = $this->add_error(
					$state['errors'],
					sprintf(
						/* translators: %1$s: Attachment ID, %2$s: Term IDs to assign to Attachment ID, %3$s: Error message when trying to assign Terms to Attachment */
						__( 'Attachment ID: %1$s, Term IDs: %2$s, Error: %3$s', 'media-library-organizer' ),
						$attachment_id,
						implode( ',', $term_ids ),
						$result->get_error_message()
					)
				);
			}
		}

		return $state;
	}

	/**
	 * Skips the current batch of Folders or Attachment to Folder relationships, and continues with the next batch.
	 *
	 * @param   array  $state   Import progress.
	 * @param   string $key     Batch key.
	 * @return  array               Import progress
	 */
	private function skip_batch( $state, $key ) {

		if ( 'folders' === $state['stage'] ) {
			$total = count( $this->get_queue() );
			$from  = (int) $state['folder_cursor'];
			$to    = min( $from + self::FOLDER_BATCH_SIZE, $total );

			$state['failed_batches'][] = sprintf(
				/* translators: %1$s: Number of the first folder in the batch, %2$s: Number of the last folder in the batch */
				__( 'Folders %1$s to %2$s could not be imported, and were skipped.', 'media-library-organizer' ),
				number_format_i18n( $from + 1 ),
				number_format_i18n( $to )
			);

			$state['folder_cursor'] = $to;

			if ( $to >= $total ) {
				$state = $this->start_attachments_stage( $state );
			}
		} else {
			$rows = $this->get_attachment_rows( $state );

			if ( empty( $rows ) ) {
				$state['stage']  = 'done';
				$state['status'] = 'complete';
			} else {
				$attachment_ids = array();
				foreach ( $rows as $row ) {
					$attachment_ids[ (int) $row->attachment_id ] = true;
				}
				$attachment_ids = array_keys( $attachment_ids );

				$state['failed_batches'][] = sprintf(
					/* translators: %s: Comma separated list of Attachment IDs */
					__( 'The following attachments could not be assigned to folders, and were skipped: %s', 'media-library-organizer' ),
					implode( ', ', $attachment_ids )
				);

				$state['attachments_processed'] += count( $attachment_ids );
				$state['attachment_cursor']      = max( $attachment_ids );
			}
		}

		unset( $state['batch_attempts'][ $key ] );

		return $this->save_state( $state );
	}

	/**
	 * Starts the Attachment assignment stage of the Import.
	 *
	 * @param   array $state    Import progress.
	 * @return  array               Import progress
	 */
	private function start_attachments_stage( $state ) {

		$state['stage']             = 'attachments';
		$state['attachment_cursor'] = 0;

		return $state;
	}

	/**
	 * Returns the next batch of Attachment to Folder relationships to import, based on the current cursor.
	 *
	 * @param   array $state    Import progress.
	 * @return  array               Rows of attachment_id and folder_id
	 */
	private function get_attachment_rows( $state ) {

		$rows = $this->query_attachment_rows(
			$state,
			array(
				'after' => (int) $state['attachment_cursor'],
				'limit' => self::ATTACHMENT_BATCH_SIZE,
			)
		);

		if ( count( $rows ) < self::ATTACHMENT_BATCH_SIZE ) {
			return $rows;
		}

		$last_attachment_id = (int) $rows[ count( $rows ) - 1 ]->attachment_id;

		$trimmed = array();
		foreach ( $rows as $row ) {
			if ( (int) $row->attachment_id !== $last_attachment_id ) {
				$trimmed[] = $row;
			}
		}

		// If all rows were for the same Attachment, query the next batch of rows for that Attachment.
		if ( empty( $trimmed ) ) {
			return $this->query_attachment_rows(
				$state,
				array(
					'attachment_id' => $last_attachment_id,
				)
			);
		}

		return $trimmed;
	}

	/**
	 * Queries the Import Source for Attachment to Folder relationships to import.
	 *
	 * @param   array $source     Import Source, or Import progress.
	 * @param   array $args       Query arguments:
	 *                            after         - read Attachment IDs greater than this;
	 *                            attachment_id - read only this Attachment's Folders;
	 *                            limit         - maximum rows to return, zero for no limit.
	 * @return  array                 Rows of attachment_id and folder_id
	 */
	private function query_attachment_rows( $source, $args ) {

		global $wpdb;

		$args = wp_parse_args(
			$args,
			array(
				'after'         => 0,
				'attachment_id' => 0,
				'limit'         => 0,
			)
		);

		$folder_ids = $this->get_queue();
		if ( empty( $folder_ids ) ) {
			return array();
		}

		$placeholders = implode( ', ', array_fill( 0, count( $folder_ids ), '%d' ) );

		if ( $args['attachment_id'] > 0 ) {
			$comparison  = '=';
			$compared_to = (int) $args['attachment_id'];
		} else {
			$comparison  = '>';
			$compared_to = (int) $args['after'];
		}

		$query_args = array_merge( $folder_ids, array( $compared_to ) );

		if ( self::STORAGE_TABLE === $source['storage'] ) {
			$table  = $this->identifier( $source['table']['relations'] );
			$object = $this->identifier( $source['table']['relations_object'] );
			$folder = $this->identifier( $source['table']['relations_folder'] );

			if ( ! $this->table_exists( $table ) ) {
				return array();
			}

			$sql = "SELECT {$object} AS attachment_id, {$folder} AS folder_id
					FROM {$table}
					WHERE {$folder} IN ({$placeholders})
					AND {$object} {$comparison} %d
					ORDER BY {$object} ASC, {$folder} ASC";
		} else {
			$sql = "SELECT {$wpdb->term_relationships}.object_id AS attachment_id,
						   {$wpdb->term_relationships}.term_taxonomy_id AS folder_id
					FROM {$wpdb->term_relationships}
					WHERE {$wpdb->term_relationships}.term_taxonomy_id IN ({$placeholders})
					AND {$wpdb->term_relationships}.object_id {$comparison} %d
					ORDER BY {$wpdb->term_relationships}.object_id ASC, {$wpdb->term_relationships}.term_taxonomy_id ASC";
		}

		if ( $args['limit'] > 0 ) {
			$sql         .= ' LIMIT %d';
			$query_args[] = (int) $args['limit'];
		}

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $query_args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Counts the number of Attachments assigned to the Import Source's Folders.
	 *
	 * @param   array $source         Import Source.
	 * @param   array $folder_ids     Source Folder IDs.
	 * @return  int
	 */
	private function count_attachments( $source, $folder_ids ) {

		global $wpdb;

		if ( empty( $folder_ids ) ) {
			return 0;
		}

		$placeholders = implode( ', ', array_fill( 0, count( $folder_ids ), '%d' ) );

		if ( self::STORAGE_TABLE === $source['storage'] ) {
			$table  = $this->identifier( $source['table']['relations'] );
			$object = $this->identifier( $source['table']['relations_object'] );
			$folder = $this->identifier( $source['table']['relations_folder'] );

			if ( ! $this->table_exists( $table ) ) {
				return 0;
			}

			$sql = "SELECT COUNT(DISTINCT {$object}) FROM {$table} WHERE {$folder} IN ({$placeholders})";
		} else {
			$sql = "SELECT COUNT(DISTINCT object_id) FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ({$placeholders})";
		}

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $folder_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	}

	/**
	 * Returns a lookup of Attachment IDs that exist in the Media Library, for the given Attachment IDs.
	 *
	 * @param   array $attachment_ids     Attachment IDs.
	 * @return  array                         Lookup of Attachment IDs that exist
	 */
	private function get_existing_attachment_ids( $attachment_ids ) {

		// Bail if there's nothing to check. An empty post__in would match every Attachment.
		if ( empty( $attachment_ids ) ) {
			return array();
		}

		$existing_ids = get_posts(
			array(
				'post_type'              => 'attachment',
				'post_status'            => 'any',
				'post__in'               => $attachment_ids,
				'fields'                 => 'ids',
				'posts_per_page'         => count( $attachment_ids ),
				'orderby'                => 'none',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$existing = array();
		foreach ( (array) $existing_ids as $existing_id ) {
			$existing[ (int) $existing_id ] = true;
		}

		return $existing;
	}

	/**
	 * Queries the Import Source for the given Folders, optionally limited to the given Folder IDs.
	 *
	 * @param   array $source     Import Source, or Import progress.
	 * @param   array $folder_ids Optional Folder IDs to limit the results to.
	 * @return  array             Folders
	 */
	private function query_folders( $source, $folder_ids = array() ) {

		global $wpdb;

		$args = array();

		if ( self::STORAGE_TABLE === $source['storage'] ) {
			$table  = $this->identifier( $source['table']['folders'] );
			$id     = $this->identifier( $source['table']['folders_id'] );
			$name   = $this->identifier( $source['table']['folders_name'] );
			$parent = $this->identifier( $source['table']['folders_parent'] );

			if ( ! $this->table_exists( $table ) ) {
				return array();
			}

			// None of the supported Plugins store a Folder description in their own tables.
			$sql = "SELECT {$id} AS id, {$name} AS name, {$parent} AS parent, '' AS description
					FROM {$table}";

			$where = array();
			if ( ! empty( $source['table']['folders_where'] ) ) {
				$where[] = $source['table']['folders_where'];
			}
			if ( ! empty( $folder_ids ) ) {
				$where[] = $id . ' IN (' . implode( ', ', array_fill( 0, count( $folder_ids ), '%d' ) ) . ')';
				$args    = $folder_ids;
			}
			if ( count( $where ) ) {
				$sql .= ' WHERE ' . implode( ' AND ', $where );
			}

			if ( ! empty( $source['table']['folders_orderby'] ) ) {
				$sql .= ' ORDER BY ' . $source['table']['folders_orderby'];
			}
		} else {
			$taxonomies = array_values( (array) $source['taxonomies'] );
			if ( empty( $taxonomies ) ) {
				return array();
			}

			$sql  = "SELECT child_tt.term_taxonomy_id AS id,
 							child_tt.description,
 							COALESCE(parent_tt.term_taxonomy_id, 0) AS parent,
 							source_terms.name
 					FROM {$wpdb->term_taxonomy} AS child_tt
 					LEFT JOIN {$wpdb->terms} AS source_terms
 					ON child_tt.term_id = source_terms.term_id
 					LEFT JOIN {$wpdb->term_taxonomy} AS parent_tt
 					ON parent_tt.term_id = child_tt.parent
 					AND parent_tt.taxonomy = child_tt.taxonomy
 					WHERE child_tt.taxonomy IN (" . implode( ', ', array_fill( 0, count( $taxonomies ), '%s' ) ) . ')';
			$args = $taxonomies;

			if ( ! empty( $folder_ids ) ) {
				$sql .= ' AND child_tt.term_taxonomy_id IN (' . implode( ', ', array_fill( 0, count( $folder_ids ), '%d' ) ) . ')';
				$args = array_merge( $args, $folder_ids );
			}
		}

		if ( empty( $args ) ) {
			$rows = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} else {
			$rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		}

		// Make the Folders associative, so the keys are the Folder IDs.
		$folders = array();
		foreach ( (array) $rows as $row ) {
			$folders[ (int) $row->id ] = $row;
		}

		return $folders;
	}

	/**
	 * Orders the given Folders by their hierarchy, so that parents are always listed before their children.
	 *
	 * @param   array $folders Folders, keyed by their source ID.
	 * @return  array          Folder IDs
	 */
	private function order_folders_by_hierarchy( $folders ) {

		$children = array();
		foreach ( $folders as $folder_id => $folder ) {
			$parent = (int) $folder->parent;

			if ( $parent <= 0 || $parent === (int) $folder_id || ! isset( $folders[ $parent ] ) ) {
				$parent = 0;
			}

			$children[ $parent ][] = (int) $folder_id;
		}

		$ordered = array();
		$added   = array();
		$stack   = isset( $children[0] ) ? array_reverse( $children[0] ) : array();

		while ( ! empty( $stack ) ) {
			$folder_id = array_pop( $stack );

			if ( isset( $added[ $folder_id ] ) ) {
				continue;
			}

			$added[ $folder_id ] = true;
			$ordered[]           = $folder_id;

			if ( isset( $children[ $folder_id ] ) ) {
				foreach ( array_reverse( $children[ $folder_id ] ) as $child_id ) {
					$stack[] = $child_id;
				}
			}
		}

		foreach ( $folders as $folder_id => $folder ) {
			if ( ! isset( $added[ (int) $folder_id ] ) ) {
				$ordered[] = (int) $folder_id;
			}
		}

		return $ordered;
	}

	/**
	 * Resolves the given Import Source name to its storage method and other details.
	 *
	 * @param   string $name   Import Source name.
	 * @param   array  $args   Optional Import arguments, such as the Taxonomies to import.
	 * @return  WP_Error|array WP_Error | Import Source
	 */
	private function resolve_source( $name, $args = array() ) {

		$sources = $this->get_sources();

		if ( ! isset( $sources[ $name ] ) ) {
			return new WP_Error(
				'media_library_organizer_import',
				sprintf(
					/* translators: %s: Import Source name */
					__( 'The import source %s is not supported.', 'media-library-organizer' ),
					$name
				)
			);
		}

		$source = array_merge(
			array(
				'label'             => $name,
				'table'             => array(),
				'taxonomies'        => array(),
				'choose_taxonomies' => false,
			),
			$sources[ $name ]
		);

		$source['name']    = $name;
		$source['storage'] = '';

		if ( ! empty( $source['choose_taxonomies'] ) && ! empty( $args['taxonomies'] ) ) {
			$source['taxonomies'] = array_values( array_map( 'sanitize_key', (array) $args['taxonomies'] ) );
		}

		if ( ! empty( $source['table'] ) && $this->table_has_folders( $source['table'] ) ) {
			$source['storage'] = self::STORAGE_TABLE;
			return $source;
		}

		if ( ! empty( $source['taxonomies'] ) && $this->taxonomies_have_terms( $source['taxonomies'] ) ) {
			$source['storage'] = self::STORAGE_TAXONOMY;
			return $source;
		}

		return new WP_Error(
			'media_library_organizer_import',
			sprintf(
				/* translators: %s: Name of the Plugin being imported from */
				__( 'No folders were found to import from %s.', 'media-library-organizer' ),
				$source['label']
			)
		);
	}

	/**
	 * Whether the given table storage descriptor's table exists and holds Folders.
	 *
	 * @param   array $table    Table storage descriptor.
	 * @return  bool
	 */
	private function table_has_folders( $table ) {

		global $wpdb;

		$table_name = $this->identifier( $table['folders'] );

		if ( ! $this->table_exists( $table_name ) ) {
			return false;
		}

		$sql = "SELECT COUNT(*) FROM {$table_name}";
		if ( ! empty( $table['folders_where'] ) ) {
			$sql .= ' WHERE ' . $table['folders_where'];
		}

		return (int) $wpdb->get_var( $sql ) > 0; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Whether any of the given Taxonomies have Terms.
	 *
	 * @param   array $taxonomies Taxonomy names.
	 * @return  bool
	 */
	private function taxonomies_have_terms( $taxonomies ) {

		global $wpdb;

		$taxonomies = array_values( (array) $taxonomies );
		if ( empty( $taxonomies ) ) {
			return false;
		}

		$placeholders = implode( ', ', array_fill( 0, count( $taxonomies ), '%s' ) );

		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				$taxonomies
			)
		);

		return $count > 0;
	}

	/**
	 * Creates a new Term for this Plugin's Taxonomy, if it does not already exist.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $term_name      Term Name.
	 * @param   string $description    Term Description.
	 * @param   int    $parent_id      Parent Term ID.
	 * @return  WP_Error|int                    WP_Error|Term ID
	 */
	private function create_term( $term_name, $description = '', $parent_id = 0 ) {

		// Check if this Term Name already exists in this Plugin's Taxonomy
		// If so, return its ID.
		$existing_term = get_term_by( 'name', $term_name, 'mlo-category' );
		if ( false !== $existing_term ) {
			return $existing_term->term_id;
		}

		// Term Name does not exist.
		// Create Term for this Plugin's Taxonomy.
		$result = wp_insert_term(
			$term_name,
			'mlo-category',
			array(
				'description' => $description,
				'parent'      => $parent_id,
			)
		);

		// Bail if an error occured.
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Return the ID.
		return $result['term_id'];
	}

	/**
	 * Returns the Import's progress in the format used by the Import screen.
	 *
	 * @param   array $state    Optional Import progress. Read from the database if omitted.
	 * @return  array               Status
	 */
	public function get_status( $state = null ) {

		if ( ! is_array( $state ) ) {
			$state = $this->get_state();
		}

		$total     = (int) $state['folders_total'] + (int) $state['attachments_total'];
		$processed = (int) $state['folder_cursor'] + (int) $state['attachments_processed'];

		if ( 'complete' === $state['status'] ) {
			$percentage = 100;
		} elseif ( $total > 0 ) {
			$percentage = min( 100, (int) round( ( $processed / $total ) * 100 ) );
		} else {
			$percentage = 0;
		}

		return array(
			'source'         => $state['source'],
			'status'         => $state['status'],
			'running'        => $this->is_running_state( $state ),
			'percentage'     => $percentage,
			'message'        => $this->get_status_message( $state ),
			'errors'         => array_values( $state['errors'] ),
			'failed_batches' => array_values( $state['failed_batches'] ),
		);
	}

	/**
	 * Returns a description of what the Import is doing, for display to the user.
	 *
	 * @param   array $state    Import progress.
	 * @return  string              Message
	 */
	private function get_status_message( $state ) {

		switch ( $state['status'] ) {
			case 'queued':
			case 'processing':
				if ( 'folders' === $state['stage'] ) {
					return sprintf(
						/* translators: %1$s: Number of folders imported, %2$s: Total number of folders to import */
						__( 'Importing folders: %1$s of %2$s.', 'media-library-organizer' ),
						number_format_i18n( (int) $state['folder_cursor'] ),
						number_format_i18n( (int) $state['folders_total'] )
					);
				}

				return sprintf(
					/* translators: %1$s: Number of attachments assigned to folders, %2$s: Total number of attachments to assign */
					__( 'Assigning attachments to folders: %1$s of %2$s.', 'media-library-organizer' ),
					number_format_i18n( (int) $state['attachments_processed'] ),
					number_format_i18n( (int) $state['attachments_total'] )
				);

			case 'complete':
				$message = sprintf(
					/* translators: %1$s: Number of folders imported, %2$s: Number of attachments assigned to folders */
					__( 'Import complete. %1$s folders and %2$s attachments were processed.', 'media-library-organizer' ),
					number_format_i18n( (int) $state['folder_cursor'] ),
					number_format_i18n( (int) $state['attachments_processed'] )
				);

				if ( (int) $state['attachments_missing'] > 0 ) {
					$message .= ' ' . sprintf(
						/* translators: %s: Number of attachments that no longer exist */
						_n(
							'%s attachment was skipped, as it no longer exists in the Media Library.',
							'%s attachments were skipped, as they no longer exist in the Media Library.',
							(int) $state['attachments_missing'],
							'media-library-organizer'
						),
						number_format_i18n( (int) $state['attachments_missing'] )
					);
				}

				return $message;

			case 'cancelled':
				return __( 'Import cancelled.', 'media-library-organizer' ) . ' ' . __( 'Folders imported so far are kept, and importing again will not duplicate them.', 'media-library-organizer' );

			default:
				return '';
		}
	}

	/**
	 * Enqueues the JS that starts and monitors Imports on the Import & Export screen.
	 */
	public function enqueue_scripts() {

		if ( ! $this->is_import_export_screen() ) {
			return;
		}

		// If SCRIPT_DEBUG is enabled, load unminified versions.
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$ext = '';
		} else {
			$ext = 'min';
		}

		wp_enqueue_script(
			$this->base->plugin->name . '-import',
			$this->base->plugin->url . 'assets/js/' . ( $ext ? $ext . '/' : '' ) . 'import' . ( $ext ? '-' . $ext : '' ) . '.js',
			array(),
			$this->base->plugin->version,
			true
		);
		wp_localize_script(
			$this->base->plugin->name . '-import',
			'media_library_organizer_import',
			array(
				'nonce'    => wp_create_nonce( 'media-library-organizer-import' ),
				'interval' => 3000,
				'status'   => $this->get_status(),
				'actions'  => array(
					'start'  => 'media_library_organizer_import_start',
					'status' => 'media_library_organizer_import_status',
					'cancel' => 'media_library_organizer_import_cancel',
				),
				'strings'  => array(
					'starting'       => __( 'Starting import...', 'media-library-organizer' ),
					'cancelling'     => __( 'Cancelling import...', 'media-library-organizer' ),
					'confirm_cancel' => __( 'Cancel this import?', 'media-library-organizer' ) . ' ' . __( 'Folders imported so far are kept, and importing again will not duplicate them.', 'media-library-organizer' ),
					'request_failed' => __( 'The site could not be reached, so the state of the import is unknown. Reload this page to check whether it is running.', 'media-library-organizer' ),
				),
			)
		);
	}

	/**
	 * Starts an Import via AJAX.
	 */
	public function ajax_start() {

		$this->verify_ajax_request();

		// The nonce is checked by verify_ajax_request(), above.
		$name = isset( $_POST['source'] ) ? sanitize_key( wp_unslash( $_POST['source'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$args = array();

		// The Import screen sends the Source's own form fields, such as the Taxonomies to
		// import from Enhanced Media Library.
		if ( isset( $_POST['args'] ) && is_array( $_POST['args'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$args = map_deep( wp_unslash( $_POST['args'] ), 'sanitize_text_field' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		$state = $this->start( $name, $args );

		if ( is_wp_error( $state ) ) {
			wp_send_json_error( $state->get_error_message() );
		}

		wp_send_json_success( $this->get_status( $state ) );
	}

	/**
	 * Returns the Import's progress via AJAX, processing a batch while the user watches.
	 */
	public function ajax_status() {

		$this->verify_ajax_request();

		$state = $this->get_state();

		// If the Import is still running, process the next batch.
		if ( $this->is_running_state( $state ) && ! $this->is_locked() ) {
			$state = $this->process();
		}

		wp_send_json_success( $this->get_status( $state ) );
	}

	/**
	 * Cancels the Import via AJAX.
	 */
	public function ajax_cancel() {

		$this->verify_ajax_request();

		wp_send_json_success( $this->get_status( $this->cancel() ) );
	}

	/**
	 * Checks the nonce and capability of an AJAX request, exiting if either fails.
	 */
	private function verify_ajax_request() {

		check_ajax_referer( 'media-library-organizer-import', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'media-library-organizer' ), 401 );
		}
	}

	/**
	 * Whether the current screen is the Plugin's Import & Export screen.
	 *
	 * @return  bool
	 */
	private function is_import_export_screen() {

		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();
		if ( is_null( $screen ) ) {
			return false;
		}

		return ( sanitize_title( $this->base->plugin->displayName ) . '_page_' . $this->base->plugin->name . '-import-export' ) === $screen->id;
	}

	/**
	 * Returns the Import's default progress.
	 *
	 * @return  array
	 */
	private function get_default_state() {

		return array(
			'status'                => 'idle',
			'stage'                 => '',
			'source'                => '',
			'label'                 => '',
			'storage'               => '',
			'table'                 => array(),
			'taxonomies'            => array(),
			'started_at'            => 0,
			'updated_at'            => 0,
			'folders_total'         => 0,
			'folder_cursor'         => 0,
			'attachments_total'     => 0,
			'attachments_processed' => 0,
			'attachments_missing'   => 0,
			'attachment_cursor'     => 0,
			'batch_attempts'        => array(),
			'failed_batches'        => array(),
			'errors'                => array(),
		);
	}

	/**
	 * Stores the Import's progress.
	 *
	 * @param   array $state Import progress.
	 * @return  array        Import progress
	 */
	private function save_state( $state ) {

		// If the Import was cancelled, don't overwrite the cancelled state with a new one.
		// This allows the Import to be resumed later, without losing the cancelled state.
		if ( 'cancelled' !== $state['status'] ) {
			$stored = get_option( self::STATE_OPTION );

			if ( is_array( $stored ) && isset( $stored['status'] ) && 'cancelled' === $stored['status'] ) {
				return array_merge( $this->get_default_state(), $stored );
			}
		}

		$state['updated_at'] = time();

		update_option( self::STATE_OPTION, $state, false );

		return $state;
	}

	/**
	 * Whether the given progress belongs to an Import that still has work to do.
	 *
	 * @param   array $state Import progress.
	 * @return  bool
	 */
	public function is_running_state( $state ) {

		return in_array( $state['status'], array( 'queued', 'processing' ), true );
	}

	/**
	 * Returns a key identifying the batch the Import is about to process.
	 *
	 * @param   array $state Import progress.
	 * @return  string       Batch key
	 */
	private function get_batch_key( $state ) {

		if ( 'folders' === $state['stage'] ) {
			return 'folders:' . (int) $state['folder_cursor'];
		}

		return 'attachments:' . (int) $state['attachment_cursor'];
	}

	/**
	 * Returns the ordered Folder IDs to import.
	 *
	 * @return  array
	 */
	private function get_queue() {

		$queue = get_option( self::QUEUE_OPTION, array() );

		return is_array( $queue ) ? $queue : array();
	}

	/**
	 * Returns the third party Folder ID to Media Library Organizer Term ID mappings.
	 *
	 * @return array
	 */
	private function get_mappings() {

		$mappings = get_option( self::MAPPINGS_OPTION, array() );

		return is_array( $mappings ) ? $mappings : array();
	}

	/**
	 * Stores the third party Folder ID to Media Library Organizer Term ID mappings.
	 *
	 * @param array $mappings Mappings.
	 */
	private function save_mappings( $mappings ) {

		update_option( self::MAPPINGS_OPTION, $mappings, false );
	}

	/**
	 * Appends an error message, up to the maximum number of errors stored.
	 *
	 * @param   array  $errors  Errors.
	 * @param   string $message Error message.
	 * @return  array           Errors
	 */
	private function add_error( $errors, $message ) {

		if ( count( $errors ) >= self::MAX_ERRORS ) {
			return $errors;
		}

		$errors[] = $message;

		return $errors;
	}

	/**
	 * Queues the Cron event that processes the next batch.
	 *
	 * @return bool Whether an event is queued
	 */
	private function schedule_next() {

		if ( wp_next_scheduled( self::CRON_HOOK ) ) {
			return true;
		}

		// Schedule the next batch to run immediately, so that the Import can continue even if the user navigates away from the Import screen.
		return ( false !== wp_schedule_single_event( time(), self::CRON_HOOK ) );
	}

	/**
	 * Removes any queued Cron event.
	 */
	private function unschedule() {

		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Claims the right to process batches, taking over a lock left behind by a request that
	 * died before it could release one.
	 *
	 * @return bool Lock acquired
	 */
	private function acquire_lock() {

		global $wpdb;

		$claim = time() . ':' . wp_generate_uuid4();
		$lock  = get_option( self::LOCK_OPTION );

		if ( false === $lock ) {
			// add_option() inserts the row, and returns false if another request inserted it
			// first, so exactly one concurrent request can win.
			if ( ! add_option( self::LOCK_OPTION, $claim, '', false ) ) {
				return false;
			}

			$this->lock_claim = $claim;

			return true;
		}

		// Another request is still working.
		if ( ! $this->is_lock_stale( $lock ) ) {
			return false;
		}

		// The lock is stale, so try to claim it.
		// This is a direct database query, because the Options API does not support conditional updates.
		$claimed = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->options,
			array( 'option_value' => $claim ),
			array(
				'option_name'  => self::LOCK_OPTION,
				'option_value' => (string) $lock,
			)
		);

		// The value was written outside of the Options API, so drop its cached copy.
		wp_cache_delete( self::LOCK_OPTION, 'options' );

		if ( 1 !== (int) $claimed ) {
			return false;
		}

		$this->lock_claim = $claim;

		return true;
	}

	/**
	 * Whether another request is currently processing batches.
	 *
	 * @return  bool
	 */
	private function is_locked() {

		$lock = get_option( self::LOCK_OPTION );

		return ( false !== $lock && ! $this->is_lock_stale( $lock ) );
	}

	/**
	 * Whether the given lock is stale, meaning that the request that took it has likely died
	 *
	 * @param  string $lock Stored lock value.
	 * @return bool
	 */
	private function is_lock_stale( $lock ) {

		$taken_at = (int) strtok( (string) $lock, ':' );

		return ( ( time() - $taken_at ) >= self::LOCK_TIMEOUT );
	}

	/**
	 * Releases the lock, if this request still holds it.
	 */
	private function release_lock() {

		global $wpdb;

		if ( is_null( $this->lock_claim ) ) {
			return;
		}

		$claim            = $this->lock_claim;
		$this->lock_claim = null;

		// Delete only this request's own claim.
		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->options,
			array(
				'option_name'  => self::LOCK_OPTION,
				'option_value' => $claim,
			)
		);

		wp_cache_delete( self::LOCK_OPTION, 'options' );
	}

	/**
	 * Whether the given database table exists.
	 *
	 * @param  string $table  Table name.
	 * @return bool
	 */
	private function table_exists( $table ) {

		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table;
	}

	/**
	 * Strips anything that isn't valid in an unquoted table or column name, as these can't
	 * be passed through $wpdb->prepare().
	 *
	 * @param  string $identifier Table or column name.
	 * @return string             Table or column name
	 */
	private function identifier( $identifier ) {

		return preg_replace( '/[^a-zA-Z0-9_]/', '', (string) $identifier );
	}
}
