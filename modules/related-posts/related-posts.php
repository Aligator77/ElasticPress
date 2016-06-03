<?php

/**
 * EP_Related_Posts
 * 
 * This class will perform a WP_Query to fetch related posts and then
 * display the returned posts below the post's content.
 * 
 * @since 0.0.1
 * 
 */
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class EP_Related_Posts {
	
	const CACHE_GROUP = 'elasticpress';

	/**
	 * Placeholder method
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		
	}

	/**
	 * Return singleton instance of class
	 *
	 * @return EP_Related_Posts
	 * @since 0.0.1
	 */
	public static function factory() {
		static $instance = false;

		if ( !$instance ) {
			$instance = new self();
			add_filter( 'ep_formatted_args', array( $instance, 'formatted_args' ), 10, 2 );
			add_filter( 'the_content', array( $instance, 'filter_content' ) );
		}

		return $instance;
	}

	/**
	 * Query for related posts.
	 * @param int $post_id Post ID you wish to find posts related to.
	 * @param int $return The number of related posts to return. Default: 4
	 * @return boolean|array Returns false or array of WP_Query results.
	 * @since 0.0.1
	 */
	public function find_related( $post_id, $return = 4 ) {
		$args	 = array(
			'more_like'		 => $post_id,
			'posts_per_page' => $return,
			's'				 => ''
		);
		$query	 = new WP_Query( $args );
		if ( !$query->have_posts() ) {
			return false;
		}

		return $query->posts;
	}

	/**
	 * Add the related posts after the content if we are on content page.
	 * @param string $content Post content.
	 * @return string Post content filtered.
	 * @since 0.0.1
	 */
	public function filter_content( $content ) {
		if ( is_search() || is_home() || is_archive() || is_category() ) {
			return $content;
		}
		$post_id		 = get_the_ID();
		$cache_key		 = md5( 'related_posts_' . $post_id );
		$related_posts	 = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false === $related_posts ) {
			$related_posts = $this->find_related( $post_id );
			wp_cache_set( $cache_key, $related_posts, self::CACHE_GROUP, 300 );
		}
		$html = $this->get_html( $related_posts );
		return $content . "\n" . $html;
	}

	/**
	 * Returns HTML for related posts section.
	 * @param array $posts Array of WP_Post objects.
	 * @return string Related posts HTML.
	 * @since 0.0.1
	 */
	public function get_html( $posts ) {

		if ( false === $posts ) {
			return '';
		}
		$html = '<h3>Related Posts</h3>';
		$html.= '<ul>';
		foreach ( $posts as $post ) {
			$html.=sprintf(
			'<li><a href="%s">%s</a></li>', esc_url( get_permalink( $post->ID ) ), esc_html( $post->post_title )
			);
		}
		$html.='</ul>';

		/**
		 * Filter the display HTML for related posts.
		 * 
		 * If developers want to customize the returned HTML for related posts or
		 * write their own HTML, they have the power to do so.
		 * 
		 * @since 0.0.1
		 * 
		 * @param string $html Default Generated HTML 
		 * @param array $posts Array of WP_Post objects.
		 */
		return apply_filters( 'ep_related_html', $html, $posts );
	}

	/**
	 * Filter for ep_formatted_args to add more like to EP query.
	 * @param array $formatted_args
	 * @param type $args
	 * @return array
	 * @since 0.0.1
	 */
	function formatted_args( $formatted_args, $args ) {
		if ( !empty( $args[ 'more_like' ] ) ) {
			$formatted_args[ 'query' ] = array(
				'more_like_this' => array(
					'ids'				 => is_array( $args[ 'more_like' ] ) ? $args[ 'more_like' ] : array( $args[ 'more_like' ] ),
					'fields'			 => array( 'post_title', 'post_content', 'terms.post_tag.name' ),
					'min_term_freq'		 => 1,
					'max_query_terms'	 => 12,
					'min_doc_freq'		 => 1,
				)
			);
		}

		return $formatted_args;
	}

}

EP_Related_Posts::factory();

/**
 * Accessor functions for methods in above class. See doc blocks above for function details.
 */
function ep_find_related( $post_id, $return = 4 ) {
	return EP_Related_Posts::factory()->find_related( $post_id, $return );
}