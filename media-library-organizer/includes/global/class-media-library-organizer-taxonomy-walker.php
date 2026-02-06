<?php
/**
 * Taxonomy Walker class.
 *
 * @package Media_Library_Organizer
 * @author Themeisle
 */

/**
 * Outputs checkboxes with names of taxonomy_termID for necessary
 * compatibility for this Plugin's functionality within
 * the Media Library.
 *
 * @since   1.0.0
 */
class Media_Library_Organizer_Taxonomy_Walker extends Walker {

	/**
	 * Holds the Taxonomy name.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	public $tree_type = 'mlo-category';

	/**
	 * Holds the database fields.
	 *
	 * @since   1.0.0
	 *
	 * @var     array<string>
	 */
	public $db_fields = array(
		'parent' => 'parent',
		'id'     => 'term_id',
	);

	/**
	 * Starts the list before the elements are added.
	 *
	 * @see Walker:start_lvl()
	 *
	 * @since 2.5.1
	 *
	 * @param string $output Used to append additional content (passed by reference).
	 * @param int    $depth  Depth of category. Used for tab indentation.
	 * @param array  $args   An array of arguments. @see wp_terms_checklist().
	 */
	public function start_lvl( &$output, $depth = 0, $args = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		$indent  = str_repeat( "\t", $depth );
		$output .= "$indent<ul class='children'>\n";
	}

	/**
	 * Ends the list of after the elements are added.
	 *
	 * @see Walker::end_lvl()
	 *
	 * @since 2.5.1
	 *
	 * @param string $output Used to append additional content (passed by reference).
	 * @param int    $depth  Depth of category. Used for tab indentation.
	 * @param array  $args   An array of arguments. @see wp_terms_checklist().
	 */
	public function end_lvl( &$output, $depth = 0, $args = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		$indent  = str_repeat( "\t", $depth );
		$output .= "$indent</ul>\n";
	}

	/**
	 * Outputs checkboxes with names of taxonomy_termID
	 *
	 * This provides the necessary compatibliity with the Media Library, specifically
	 * when media-view.php reads the selected Term IDs and saves them against the
	 * attachment.
	 *
	 * Trying to use attachments[taxonomy][] fails.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $output     Passed by reference. Used to append additional content.
	 * @param   object $category   The current term object.
	 * @param   int    $depth      Depth of the term in reference to parents. Default 0.
	 * @param   array  $args       An array of arguments. @see wp_terms_checklist().
	 * @param   int    $id         ID of the current term.
	 */
	public function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		$taxonomy = $args['taxonomy'];

		$args['popular_cats'] = empty( $args['popular_cats'] ) ? array() : $args['popular_cats'];
		$class                = in_array( $category->term_id, $args['popular_cats'], true ) ? ' class="popular-category"' : '';

		$args['selected_cats'] = empty( $args['selected_cats'] ) ? array() : $args['selected_cats'];

		if ( ! empty( $args['list_only'] ) ) {
			$aria_checked = 'false';
			$inner_class  = 'category';

			if ( in_array( $category->term_id, $args['selected_cats'], true ) ) {
				$inner_class .= ' selected';
				$aria_checked = 'true';
			}

			/** This filter is documented in wp-includes/category-template.php */
			$output .= "\n" . '<li' . $class . '>' .
				'<div class="' . $inner_class . '" data-term-id=' . $category->term_id .
				' tabindex="0" role="checkbox" aria-checked="' . $aria_checked . '">' .
				esc_html( apply_filters( 'the_category', $category->name ) ) . '</div>';
		} else {
			/** This filter is documented in wp-includes/category-template.php */
			// Note: <input> is modified here.
			$output .= "\n<li id='{$taxonomy}-{$category->term_id}'$class>" .
				'<label class="selectit"><input value="1" type="checkbox" name="' . $taxonomy . '_' . $category->term_id . '" class="check" data-taxonomy="' . $taxonomy . '"' .
				checked( in_array( $category->term_id, $args['selected_cats'], true ), true, false ) .
				disabled( empty( $args['disabled'] ), false, false ) . ' /> ' .
				esc_html( apply_filters( 'the_category', $category->name ) ) . '</label>';
		}
	}

	/**
	 * Ends the element output, if needed.
	 *
	 * @see Walker::end_el()
	 *
	 * @since 2.5.1
	 *
	 * @param string $output   Used to append additional content (passed by reference).
	 * @param object $category The current term object.
	 * @param int    $depth    Depth of the term in reference to parents. Default 0.
	 * @param array  $args     An array of arguments. @see wp_terms_checklist().
	 */
	public function end_el( &$output, $category, $depth = 0, $args = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		$output .= "</li>\n";
	}
}
