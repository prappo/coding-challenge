<?php
/**
 * Block class.
 *
 * @package SiteCounts
 */

namespace XWP\SiteCounts;

use WP_Block;
use WP_Query;

/**
 * The Site Counts dynamic block.
 *
 * Registers and renders the dynamic block.
 */
class Block {

	/**
	 * The Plugin instance.
	 *
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * Instantiates the class.
	 *
	 * @param Plugin $plugin The plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Adds the action to register the block.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Registers the block.
	 */
	public function register_block() {
		register_block_type_from_metadata(
			$this->plugin->dir(),
			[
				'render_callback' => [ $this, 'render_callback' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array    $attributes The attributes for the block.
	 * @param string   $content    The block content, if any.
	 * @param WP_Block $block      The instance of this block.
	 * @return string The markup of the block.
	 */
	public function render_callback( $attributes, $content, $block ) {
		$post_types = get_post_types(['public' => true]);
		$class_name = $attributes['className'];
		$cache_identifier_key = md5("uniquekey");
		$result = wp_cache_get($cache_identifier_key, 'site-counts');

		if ($result) {
			return $result;
		}

		ob_start();
?>
		<div class="<?php echo esc_attr($class_name); ?>">
			<h2><?php esc_html_e('Post Counts', 'site-counts'); ?></h2>
			<ul>
				<?php
				foreach ($post_types as $post_type_slug) :
					$post_type_object = get_post_type_object($post_type_slug);
					$post_count       = wp_count_posts($post_type_slug)->publish;

				?>
					<li>
						<?php echo sprintf(__('There are %1$d %2$s.', 'site-counts'), $post_count, esc_html($post_type_object->labels->name)); ?>
					</li>
				<?php
				endforeach;
				?>
			</ul>
			<p><?php echo sprintf(__('The current post ID is %d.', 'site-counts'), get_the_ID()); ?></p>

			<?php
			$query = new WP_Query(
				[
					'post_type'      => ['post', 'page'],
					'post_status'    => 'any',
					'posts_per_page' => 5,
					'date_query'     => [
						[
							'hour'    => 9,
							'compare' => '>=',
						],
						[
							'hour'    => 17,
							'compare' => '<=',
						],
					],
					'tax_query'      => [
						'relation' => 'AND',
						[
							'taxonomy' => 'post_tag',
							'field'    => 'slug',
							'terms'    => ['foo'],
						],
						[
							'taxonomy'         => 'category',
							'field'            => 'name',
							'terms'            => ['baz'],
							'include_children' => false,
						],
					],
				]
			);

			?>
			<h2><?php echo esc_html__('Any 5 posts with the tag of foo and the category of baz', 'site-counts'); ?></h2>
			<ul>
				<?php

				if ($query->have_posts()) :
					while ($query->have_posts()) :
						$query->the_post();
				?>
						<li><?php echo esc_html(get_the_title()); ?></li>
				<?php
					endwhile;
				endif;

				?>
			</ul>
		</div>
<?php
		$result = ob_get_clean();
		wp_cache_set($cache_identifier_key, $result, 'site-counts', 5 * MINUTE_IN_SECONDS);

		return $result;
	}
}
