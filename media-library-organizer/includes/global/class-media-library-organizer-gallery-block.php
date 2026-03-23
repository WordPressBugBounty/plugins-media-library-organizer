<?php
/**
 * Gallery Block.
 *
 * @package Media_Library_Organizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Media_Library_Organizer_Gallery_Block
 */
class Media_Library_Organizer_Gallery_Block {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_block_assets' ) );
	}

	/**
	 * Check if Pro version is active.
	 */
	private function is_pro() {
		if ( ! defined( 'MEDIA_LIBRARY_ORGANIZER_PRO_PLUGIN_VERSION' ) || ! class_exists( 'Media_Library_Organizer_Pro' ) ) {
			return false;
		}

		return function_exists( 'Media_Library_Organizer_Pro' ) && Media_Library_Organizer_Pro()->check_license_key_valid();
	}

	/**
	 * Registers the block.
	 */
	public function register_block() {
		$mlo = Media_Library_Organizer();

		register_block_type(
			'mlo/gallery',
			array(
				'render_callback'       => array( $this, 'render_block' ),
				'attributes'            => array(
					'categoryIds'     => array(
						'type'    => 'array',
						'default' => array(),
					),
					'includeSubcats'  => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'imageLimit'      => array(
						'type'    => 'number',
						'default' => 50,
					),
					'orderBy'         => array(
						'type'    => 'string',
						'default' => 'date_desc',
					),
					'layout'          => array(
						'type'    => 'string',
						'default' => 'grid',
					),
					'columns'         => array(
						'type'    => 'number',
						'default' => 3,
					),
					'gap'             => array(
						'type'    => 'number',
						'default' => 10,
					),
					'aspectRatio'     => array(
						'type'    => 'string',
						'default' => 'square',
					),
					'rowHeight'       => array(
						'type'    => 'number',
						'default' => 250,
					),
					'enableLightbox'  => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showCaption'     => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showCounter'     => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'enableFilter'    => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'filterStyle'     => array(
						'type'    => 'string',
						'default' => 'pills',
					),
					'showAllButton'   => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'filterAnimation' => array(
						'type'    => 'string',
						'default' => 'fade',
					),
					'borderRadius'    => array(
						'type'    => 'number',
						'default' => 4,
					),
					'hoverEffect'     => array(
						'type'    => 'string',
						'default' => 'zoom',
					),
					'captionPosition' => array(
						'type'    => 'string',
						'default' => 'hidden',
					),
					'imageSize'       => array(
						'type'    => 'string',
						'default' => 'medium_large',
					),
					'autoplay'        => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'autoplaySpeed'   => array(
						'type'    => 'number',
						'default' => 3000,
					),
					'showDots'        => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showArrows'      => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'slidesVisible'   => array(
						'type'    => 'number',
						'default' => 1,
					),
				),
				'editor_script_handles' => array( $mlo->plugin->name . '-gallery-block' ),
				'editor_style_handles'  => array( $mlo->plugin->name . '-gallery-block' ),
				'style_handles'         => array( $mlo->plugin->name . '-gallery-block' ),
				'script_handles'        => array( $mlo->plugin->name . '-gallery-block-frontend' ),
			)
		);
	}

	/**
	 * Enqueue assets and data for editor and frontend
	 */
	public function enqueue_block_editor_assets() {
		$mlo     = Media_Library_Organizer();
		$depends = include $mlo->plugin->folder . 'assets/build/gallery/index.asset.php';
		$depends = isset( $depends['dependencies'] ) ? $depends['dependencies'] : array();

		wp_register_script(
			$mlo->plugin->name . '-gallery-block',
			$mlo->plugin->url . 'assets/build/gallery/index.js',
			$depends,
			$mlo->plugin->version,
			true
		);
		wp_set_script_translations( $mlo->plugin->name . '-gallery-block', 'media-library-organizer' );

		wp_localize_script(
			$mlo->plugin->name . '-gallery-block',
			'mloGalleryBlockData',
			array(
				'isPro'      => $this->is_pro(),
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'mlo_gallery_block_nonce' ),
				'restUrl'    => esc_url_raw( rest_url() ),
				'restNonce'  => wp_create_nonce( 'wp_rest' ),
				'categories' => $this->get_categories_for_js(),
				'imageSizes' => $this->get_image_sizes(),
			)
		);

		wp_register_style(
			$mlo->plugin->name . '-gallery-block',
			$mlo->plugin->url . 'assets/build/gallery/style-index.css',
			array(),
			$mlo->plugin->version
		);
	}

	/**
	 * Register frontend assets.
	 */
	public function enqueue_block_assets() {
		$mlo = Media_Library_Organizer();
		$ext = ( $mlo->dashboard->should_load_minified_js() ? 'min' : '' );

		wp_register_style(
			$mlo->plugin->name . '-gallery-block',
			$mlo->plugin->url . 'assets/build/gallery/style-index.css',
			array(),
			$mlo->plugin->version
		);

		wp_register_script(
			$mlo->plugin->name . '-gallery-block-frontend',
			$mlo->plugin->url . 'assets/js/' . ( $ext ? $ext . '/' : '' ) . 'gallery-block' . ( $ext ? '-' . $ext : '' ) . '.js',
			array(),
			$mlo->plugin->version,
			true
		);
	}

	/**
	 * Get MLO categories for the block editor
	 */
	private function get_categories_for_js() {
		$categories = array();
		$taxonomies = Media_Library_Organizer()->get_class( 'taxonomies' )->get_taxonomies();
		$terms      = array();

		foreach ( $taxonomies as $tax => $value ) {
			if ( taxonomy_exists( $tax ) ) {
				$tax_terms = get_terms(
					array(
						'taxonomy'   => $tax,
						'hide_empty' => false,
					)
				);
				if ( ! is_wp_error( $tax_terms ) ) {
					$terms = array_merge( $terms, $tax_terms );
				}
			}
		}

		if ( ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$categories[] = array(
					'id'       => $term->term_id,
					'name'     => $term->name,
					'slug'     => $term->slug,
					'parent'   => $term->parent,
					'count'    => $term->count,
					'taxonomy' => $term->taxonomy,
				);
			}
		}
		return $categories;
	}

	/**
	 * Get registered image sizes
	 */
	private function get_image_sizes() {
		$sizes      = array();
		$registered = get_intermediate_image_sizes();
		foreach ( $registered as $size ) {
			$sizes[] = array(
				'value' => $size,
				'label' => ucwords( str_replace( '_', ' ', $size ) ),
			);
		}
		return $sizes;
	}

	/**
	 * Render the block HTML.
	 *
	 * @param array $attributes Block attributes.
	 * @return string HTML output for the block.
	 */
	public function render_block( $attributes ) {
		$category_ids     = isset( $attributes['categoryIds'] ) ? array_map( 'intval', $attributes['categoryIds'] ) : array();
		$include_subcats  = isset( $attributes['includeSubcats'] ) ? (bool) $attributes['includeSubcats'] : false;
		$image_limit      = isset( $attributes['imageLimit'] ) ? max( 0, intval( $attributes['imageLimit'] ) ) : 50;
		$order_by         = isset( $attributes['orderBy'] ) ? sanitize_text_field( $attributes['orderBy'] ) : 'date_desc';
		$layout           = isset( $attributes['layout'] ) ? sanitize_text_field( $attributes['layout'] ) : 'grid';
		$columns          = isset( $attributes['columns'] ) ? max( 2, min( 6, intval( $attributes['columns'] ) ) ) : 3;
		$gap              = isset( $attributes['gap'] ) ? max( 0, min( 40, intval( $attributes['gap'] ) ) ) : 10;
		$aspect_ratio     = isset( $attributes['aspectRatio'] ) ? sanitize_text_field( $attributes['aspectRatio'] ) : 'square';
		$row_height       = isset( $attributes['rowHeight'] ) ? max( 150, min( 400, intval( $attributes['rowHeight'] ) ) ) : 250;
		$enable_lightbox  = isset( $attributes['enableLightbox'] ) ? (bool) $attributes['enableLightbox'] : true;
		$show_caption     = isset( $attributes['showCaption'] ) ? (bool) $attributes['showCaption'] : true;
		$show_counter     = isset( $attributes['showCounter'] ) ? (bool) $attributes['showCounter'] : true;
		$enable_filter    = isset( $attributes['enableFilter'] ) ? (bool) $attributes['enableFilter'] : false;
		$filter_style     = isset( $attributes['filterStyle'] ) ? sanitize_text_field( $attributes['filterStyle'] ) : 'pills';
		$show_all_button  = isset( $attributes['showAllButton'] ) ? (bool) $attributes['showAllButton'] : true;
		$filter_animation = isset( $attributes['filterAnimation'] ) ? sanitize_text_field( $attributes['filterAnimation'] ) : 'fade';
		$border_radius    = isset( $attributes['borderRadius'] ) ? max( 0, min( 20, intval( $attributes['borderRadius'] ) ) ) : 4;
		$hover_effect     = isset( $attributes['hoverEffect'] ) ? sanitize_text_field( $attributes['hoverEffect'] ) : 'zoom';
		$caption_position = isset( $attributes['captionPosition'] ) ? sanitize_text_field( $attributes['captionPosition'] ) : 'hidden';
		$image_size       = isset( $attributes['imageSize'] ) ? sanitize_text_field( $attributes['imageSize'] ) : 'medium_large';
		$autoplay         = isset( $attributes['autoplay'] ) ? (bool) $attributes['autoplay'] : false;
		$autoplay_speed   = isset( $attributes['autoplaySpeed'] ) ? max( 1000, intval( $attributes['autoplaySpeed'] ) ) : 3000;
		$show_dots        = isset( $attributes['showDots'] ) ? (bool) $attributes['showDots'] : true;
		$show_arrows      = isset( $attributes['showArrows'] ) ? (bool) $attributes['showArrows'] : true;
		$slides_visible   = isset( $attributes['slidesVisible'] ) ? max( 1, min( 5, intval( $attributes['slidesVisible'] ) ) ) : 1;

		$is_pro = $this->is_pro();

		$pro_layouts = array( 'masonry', 'carousel', 'justified' );
		if ( in_array( $layout, $pro_layouts, true ) && ! $is_pro ) {
			$layout = 'grid';
		}

		if ( empty( $category_ids ) ) {
			return '<div class="mlo-gallery-block mlo-gallery-empty"><p>' . esc_html__( 'Please select one or more MLO categories to display a gallery.', 'media-library-organizer' ) . '</p></div>';
		}

		$images = $this->get_images( $category_ids, $include_subcats, $image_limit, $order_by );

		if ( empty( $images ) ) {
			return '<div class="mlo-gallery-block mlo-gallery-empty"><p>' . esc_html__( 'No images found in the selected categories.', 'media-library-organizer' ) . '</p></div>';
		}

		$gallery_id = 'mlo-gallery-' . md5( implode( '-', $category_ids ) . $layout . time() );
		$css_vars   = sprintf(
			'--mlo-columns: %d; --mlo-gap: %dpx; --mlo-radius: %dpx; --mlo-row-height: %dpx;',
			$columns,
			$gap,
			$border_radius,
			$row_height
		);

		$wrapper_classes = array(
			'mlo-gallery-block',
			'mlo-gallery-layout-' . $layout,
			'mlo-gallery-hover-' . $hover_effect,
			'mlo-gallery-caption-' . $caption_position,
			'mlo-gallery-ratio-' . str_replace( ':', '-', $aspect_ratio ),
		);
		if ( isset( $attributes['className'] ) ) {
			$wrapper_classes[] = sanitize_text_field( $attributes['className'] );
		}

		$data_attrs = array(
			'data-layout'         => $layout,
			'data-lightbox'       => $enable_lightbox ? '1' : '0',
			'data-counter'        => $show_counter ? '1' : '0',
			'data-animation'      => $filter_animation,
			'data-autoplay'       => $autoplay ? '1' : '0',
			'data-autoplay-speed' => $autoplay_speed,
			'data-slides-visible' => $slides_visible,
			'data-show-dots'      => $show_dots ? '1' : '0',
			'data-show-arrows'    => $show_arrows ? '1' : '0',
		);

		$data_attr_string = '';
		foreach ( $data_attrs as $key => $val ) {
			$data_attr_string .= ' ' . esc_attr( $key ) . '="' . esc_attr( $val ) . '"';
		}

		ob_start();
		?>
		<div id="<?php echo esc_attr( $gallery_id ); ?>"
			class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>"
			style="<?php echo esc_attr( $css_vars ); ?>"
			<?php echo $data_attr_string; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

			<?php if ( $enable_filter && count( $category_ids ) > 1 ) : ?>
				<?php $this->render_filter_bar( $category_ids, $filter_style, $show_all_button, $gallery_id ); ?>
				<div class="mlo-filter-empty-message" style="display: none; padding: 30px; text-align: center; color: #757575; font-size: 14px;">
					<?php esc_html_e( 'No images found in the selected category.', 'media-library-organizer' ); ?>
				</div>
			<?php endif; ?>

			<?php if ( 'carousel' === $layout && $is_pro ) : ?>
				<div class="mlo-carousel-wrapper">
					<div class="mlo-carousel-track">
					<?php else : ?>
						<div class="mlo-gallery-grid">
						<?php endif; ?>

						<?php
						$taxonomies = Media_Library_Organizer()->get_class( 'taxonomies' )->get_taxonomies();
						$taxonomies = array_keys( $taxonomies );

						$image_ids = wp_list_pluck( $images, 'ID' );
						$all_terms = wp_get_object_terms( $image_ids, $taxonomies, array( 'fields' => 'all_with_object_id' ) );

						$terms_by_image = array();
						if ( ! is_wp_error( $all_terms ) ) {
							foreach ( $all_terms as $term ) {
								if ( ! isset( $term->object_id ) ) {
									continue;
								}
								if ( ! isset( $terms_by_image[ $term->object_id ] ) ) {
									$terms_by_image[ $term->object_id ] = array();
								}
								$terms_by_image[ $term->object_id ][] = $term->term_id;
							}
						}

						foreach ( $images as $index => $image ) :
							$img_src   = wp_get_attachment_image_url( $image->ID, $image_size );
							$img_full  = wp_get_attachment_image_url( $image->ID, 'full' );
							$img_alt   = get_post_meta( $image->ID, '_wp_attachment_image_alt', true );
							$img_title = get_the_title( $image->ID );
							$caption   = wp_get_attachment_caption( $image->ID );

							if ( ! $img_src ) {
								continue;
							}

							$img_cats = isset( $terms_by_image[ $image->ID ] ) ? $terms_by_image[ $image->ID ] : array();
							$cat_data = implode( ' ', array_map( 'intval', (array) $img_cats ) );

							$item_classes = array( 'mlo-gallery-item' );
							if ( 'carousel' === $layout ) {
								$item_classes[] = 'mlo-carousel-slide';
							}
							?>
							<figure class="<?php echo esc_attr( implode( ' ', $item_classes ) ); ?>"
								data-index="<?php echo esc_attr( $index ); ?>"
								data-cats="<?php echo esc_attr( $cat_data ); ?>">

								<?php if ( $enable_lightbox ) : ?>
									<a href="<?php echo esc_url( $img_full ); ?>"
										class="mlo-lightbox-trigger"
										data-caption="<?php echo esc_attr( $caption ? $caption : $img_title ); ?>"
										data-index="<?php echo esc_attr( $index ); ?>"
										data-gallery="<?php echo esc_attr( $gallery_id ); ?>">
									<?php endif; ?>

									<img src="<?php echo esc_url( $img_src ); ?>"
										alt="<?php echo esc_attr( $img_alt ? $img_alt : $img_title ); ?>"
										loading="lazy"
										class="mlo-gallery-image" />

									<?php if ( 'overlay' === $hover_effect ) : ?>
										<div class="mlo-overlay">
											<span class="mlo-overlay-icon">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32" height="32">
													<path fill="#fff" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" />
												</svg>
											</span>
											<?php if ( 'overlay' === $caption_position && $caption ) : ?>
												<p class="mlo-overlay-caption"><?php echo esc_html( $caption ); ?></p>
											<?php endif; ?>
										</div>
									<?php endif; ?>

									<?php if ( 'fade' === $hover_effect && $caption ) : ?>
										<div class="mlo-fade-caption"><?php echo esc_html( $caption ); ?></div>
									<?php endif; ?>

									<?php if ( $enable_lightbox ) : ?>
									</a><?php endif; ?>

								<?php if ( 'below' === $caption_position && $caption ) : ?>
									<figcaption class="mlo-caption-below"><?php echo esc_html( $caption ); ?></figcaption>
								<?php endif; ?>

							</figure>
						<?php endforeach; ?>

						<?php if ( 'carousel' === $layout && $is_pro ) : ?>
						</div><!-- .mlo-carousel-track -->
							<?php if ( $show_arrows ) : ?>
							<button type="button" class="mlo-carousel-prev" aria-label="<?php esc_attr_e( 'Previous', 'media-library-organizer' ); ?>">&#8249;</button>
							<button type="button" class="mlo-carousel-next" aria-label="<?php esc_attr_e( 'Next', 'media-library-organizer' ); ?>">&#8250;</button>
						<?php endif; ?>
							<?php if ( $show_dots ) : ?>
							<div class="mlo-carousel-dots"></div>
						<?php endif; ?>
					</div><!-- .mlo-carousel-wrapper -->
				<?php else : ?>
				</div><!-- .mlo-gallery-grid -->
			<?php endif; ?>

			<?php if ( $enable_lightbox ) : ?>
				<div class="mlo-lightbox-overlay" id="<?php echo esc_attr( $gallery_id ); ?>-lightbox" aria-modal="true" role="dialog" aria-hidden="true">
					<div class="mlo-lightbox-inner">
						<button class="mlo-lightbox-close" aria-label="<?php esc_attr_e( 'Close', 'media-library-organizer' ); ?>">&#10005;</button>
						<?php if ( $show_counter ) : ?>
							<div class="mlo-lightbox-counter"></div>
						<?php endif; ?>
						<button class="mlo-lightbox-prev" aria-label="<?php esc_attr_e( 'Previous image', 'media-library-organizer' ); ?>">&#8249;</button>
						<div class="mlo-lightbox-img-wrap">
							<img class="mlo-lightbox-img" src="" alt="" />
							<?php if ( $show_caption ) : ?>
								<p class="mlo-lightbox-caption"></p>
							<?php endif; ?>
						</div>
						<button class="mlo-lightbox-next" aria-label="<?php esc_attr_e( 'Next image', 'media-library-organizer' ); ?>">&#8250;</button>
					</div>
				</div>
			<?php endif; ?>

		</div><!-- .mlo-gallery-block -->
		<?php
		return ob_get_clean();
	}

	/**
	 * Get images based on taxonomy terms.
	 *
	 * @param array  $category_ids Array of category IDs to filter by.
	 * @param bool   $include_subcats Whether to include subcategories.
	 * @param int    $limit Number of images to retrieve.
	 * @param string $order_by Order by criteria.
	 * @return array Array of WP_Post objects for the attachments.
	 */
	private function get_images( $category_ids, $include_subcats, $limit, $order_by ) {
		if ( empty( $category_ids ) ) {
			return array();
		}

		$taxonomies = Media_Library_Organizer()->get_class( 'taxonomies' )->get_taxonomies();

		$orderby = 'date';
		$order   = 'DESC';
		switch ( $order_by ) {
			case 'date_asc':
				$orderby = 'date';
				$order   = 'ASC';
				break;
			case 'date_desc':
				$orderby = 'date';
				$order   = 'DESC';
				break;
			case 'title_asc':
				$orderby = 'title';
				$order   = 'ASC';
				break;
			case 'title_desc':
				$orderby = 'title';
				$order   = 'DESC';
				break;
			case 'random':
				$orderby = 'rand';
				$order   = 'DESC';
				break;
			case 'menu_order':
				$orderby = 'menu_order';
				$order   = 'ASC';
				break;
		}

		$tax_queries = array( 'relation' => 'OR' );
		foreach ( $taxonomies as $tax => $value ) {
			if ( taxonomy_exists( $tax ) ) {
				$tax_queries[] = array(
					'taxonomy'         => $tax,
					'field'            => 'term_id',
					'terms'            => $category_ids,
					'include_children' => $include_subcats,
				);
			}
		}

		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => 'image',
			'posts_per_page' => $limit > 0 ? $limit : -1,
			'orderby'        => $orderby,
			'order'          => $order,
			'tax_query'      => $tax_queries,
		);

		return get_posts( $args );
	}

	/**
	 * Render filter bar.
	 *
	 * @param array  $category_ids Array of category IDs to display in filter.
	 * @param string $style Filter style (pills or dropdown).
	 * @param bool   $show_all Whether to show "All" button.
	 * @param string $gallery_id Unique ID of the gallery for data attributes.
	 * @return void
	 */
	private function render_filter_bar( $category_ids, $style, $show_all, $gallery_id ) {
		$categories = array();
		foreach ( $category_ids as $cat_id ) {
			$taxonomies = Media_Library_Organizer()->get_class( 'taxonomies' )->get_taxonomies();
			foreach ( $taxonomies as $tax => $value ) {
				$term = get_term( $cat_id, $tax );
				if ( $term && ! is_wp_error( $term ) ) {
					$categories[] = $term;
					break;
				}
			}
		}

		if ( empty( $categories ) ) {
			return;
		}

		echo '<div class="mlo-filter-bar mlo-filter-' . esc_attr( $style ) . '" data-gallery="' . esc_attr( $gallery_id ) . '">';

		if ( 'dropdown' === $style ) {
			echo '<select class="mlo-filter-select">';
			if ( $show_all ) {
				echo '<option value="all">' . esc_html__( 'All', 'media-library-organizer' ) . '</option>';
			}
			foreach ( $categories as $cat ) {
				echo '<option value="' . (int) $cat->term_id . '">' . esc_html( $cat->name ) . '</option>';
			}
			echo '</select>';
		} else {
			if ( $show_all ) {
				echo '<button type="button" class="mlo-filter-btn active" data-cat="all">' . esc_html__( 'All', 'media-library-organizer' ) . '</button>';
			}
			foreach ( $categories as $cat ) {
				echo '<button type="button" class="mlo-filter-btn" data-cat="' . (int) $cat->term_id . '">' . esc_html( $cat->name ) . '</button>';
			}
		}

		echo '</div>';
	}
}
