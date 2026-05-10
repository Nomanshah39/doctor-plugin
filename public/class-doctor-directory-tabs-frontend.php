<?php
/**
 * Frontend shortcode rendering.
 *
 * @package Doctor_Directory_Tabs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Renders [doctor-list-a-to-z]. */
class Doctor_Directory_Tabs_Frontend {
	const SHORTCODE = 'doctor-list-a-to-z';

	/** @var Doctor_Directory_Tabs_Cache_Manager */
	private $cache;

	/** Constructor. */
	public function __construct( $cache ) {
		$this->cache = $cache;
		add_shortcode( self::SHORTCODE, array( $this, 'shortcode' ) );
	}

	/**
	 * Shortcode callback. Returns output; never echoes.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function shortcode( $atts ) {
		$atts = self::sanitize_atts( $atts );
		wp_enqueue_style( 'ddt-frontend' );
		wp_enqueue_script( 'ddt-frontend' );

		$query_specialty = isset( $_GET['ddt_specialty'] ) ? sanitize_title( wp_unslash( $_GET['ddt_specialty'] ) ) : '';
		$active_specialty = $query_specialty ? $query_specialty : $atts['specialty'];
		$doctors          = $this->get_doctors( $atts, $active_specialty );
		$specialties      = $this->get_specialties( $atts['group'] );
		$instance_id      = wp_unique_id( 'ddt-directory-' );

		ob_start();
		?>
		<div id="<?php echo esc_attr( $instance_id ); ?>" class="ddt-directory" data-group="<?php echo esc_attr( $atts['group'] ); ?>" data-columns="<?php echo esc_attr( $atts['columns'] ); ?>" data-image-size="<?php echo esc_attr( $atts['image_size'] ); ?>" data-show-all="<?php echo esc_attr( $atts['show_all'] ? 'true' : 'false' ); ?>">
			<div class="ddt-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Filter doctors by specialty', 'doctor-directory-tabs' ); ?>">
				<?php if ( $atts['show_all'] ) : ?>
					<a class="ddt-tab <?php echo '' === $active_specialty ? 'is-active' : ''; ?>" role="tab" aria-selected="<?php echo '' === $active_specialty ? 'true' : 'false'; ?>" href="<?php echo esc_url( remove_query_arg( 'ddt_specialty' ) ); ?>" data-specialty=""><?php esc_html_e( 'All Doctors', 'doctor-directory-tabs' ); ?></a>
				<?php endif; ?>
				<?php foreach ( $specialties as $term ) : ?>
					<a class="ddt-tab <?php echo $active_specialty === $term->slug ? 'is-active' : ''; ?>" role="tab" aria-selected="<?php echo $active_specialty === $term->slug ? 'true' : 'false'; ?>" href="<?php echo esc_url( add_query_arg( 'ddt_specialty', $term->slug ) ); ?>" data-specialty="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></a>
				<?php endforeach; ?>
			</div>
			<div class="ddt-results-wrap">
				<div class="ddt-loading" hidden aria-live="polite"><?php esc_html_e( 'Loading doctors…', 'doctor-directory-tabs' ); ?></div>
				<div class="ddt-results" aria-live="polite">
					<?php echo $this->render_doctors( $doctors, $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escapes all dynamic values internally. ?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/** Sanitize shortcode attributes. */
	public static function sanitize_atts( $atts ) {
		$atts = shortcode_atts(
			array(
				'group'      => '',
				'specialty'  => '',
				'show_all'   => 'true',
				'columns'    => '3',
				'image_size' => 'medium',
			),
			(array) $atts,
			self::SHORTCODE
		);

		$columns = absint( $atts['columns'] );
		return array(
			'group'      => sanitize_title( $atts['group'] ),
			'specialty'  => sanitize_title( $atts['specialty'] ),
			'show_all'   => filter_var( $atts['show_all'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) !== false,
			'columns'    => $columns >= 1 && $columns <= 4 ? $columns : 3,
			'image_size' => Doctor_Directory_Tabs_Helpers::sanitize_image_size( $atts['image_size'] ),
		);
	}

	/** Get specialties, cached. */
	public function get_specialties( $group = '' ) {
		$key = $this->cache->key( 'specialties', array( 'group' => $group ) );
		$cached = $this->cache->get( $key );
		if ( false !== $cached ) {
			return $cached;
		}

		$args = array(
			'taxonomy'   => Doctor_Directory_Tabs_Taxonomies::SPECIALTY,
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		);
		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) ) {
			$terms = array();
		}
		$this->cache->set( $key, $terms );
		return $terms;
	}

	/** Get sorted doctor posts. */
	public function get_doctors( array $atts, $specialty = '' ) {
		$key = $this->cache->key( 'doctors', array( 'atts' => $atts, 'specialty' => $specialty ) );
		$cached = $this->cache->get( $key );
		if ( false !== $cached ) {
			return $cached;
		}

		$tax_query = array();
		if ( $specialty ) {
			$tax_query[] = array( 'taxonomy' => Doctor_Directory_Tabs_Taxonomies::SPECIALTY, 'field' => 'slug', 'terms' => $specialty );
		}
		if ( ! empty( $atts['group'] ) ) {
			$tax_query[] = array( 'taxonomy' => Doctor_Directory_Tabs_Taxonomies::GROUP, 'field' => 'slug', 'terms' => $atts['group'] );
		}

		$query = new WP_Query(
			array(
				'post_type'              => Doctor_Directory_Tabs_Post_Type::POST_TYPE,
				'post_status'            => 'publish',
				'posts_per_page'         => 500,
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => true,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'tax_query'              => count( $tax_query ) > 1 ? array_merge( array( 'relation' => 'AND' ), $tax_query ) : $tax_query,
			)
		);
		$posts = $query->posts;
		usort( $posts, array( $this, 'sort_doctors' ) );
		$this->cache->set( $key, $posts );
		return $posts;
	}

	/** Sort display order, last name, first name. */
	private function sort_doctors( $a, $b ) {
		$ao = get_post_meta( $a->ID, Doctor_Directory_Tabs_Helpers::DISPLAY_ORDER, true );
		$bo = get_post_meta( $b->ID, Doctor_Directory_Tabs_Helpers::DISPLAY_ORDER, true );
		if ( '' !== $ao || '' !== $bo ) {
			if ( '' === $ao ) {
				return 1;
			}
			if ( '' === $bo ) {
				return -1;
			}
			if ( (int) $ao !== (int) $bo ) {
				return (int) $ao <=> (int) $bo;
			}
		}
		$al = strtolower( (string) get_post_meta( $a->ID, Doctor_Directory_Tabs_Helpers::LAST_NAME, true ) );
		$bl = strtolower( (string) get_post_meta( $b->ID, Doctor_Directory_Tabs_Helpers::LAST_NAME, true ) );
		$af = strtolower( (string) get_post_meta( $a->ID, Doctor_Directory_Tabs_Helpers::FIRST_NAME, true ) );
		$bf = strtolower( (string) get_post_meta( $b->ID, Doctor_Directory_Tabs_Helpers::FIRST_NAME, true ) );
		return array( $al, $af, strtolower( $a->post_title ) ) <=> array( $bl, $bf, strtolower( $b->post_title ) );
	}

	/** Render doctors grouped by Group taxonomy. */
	public function render_doctors( array $doctors, array $atts ) {
		if ( empty( $doctors ) ) {
			return '<p class="ddt-empty">' . esc_html__( 'No doctors found.', 'doctor-directory-tabs' ) . '</p>';
		}
		$groups = array();
		foreach ( $doctors as $doctor ) {
			$terms = get_the_terms( $doctor->ID, Doctor_Directory_Tabs_Taxonomies::GROUP );
			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				$groups[ __( 'Other Doctors', 'doctor-directory-tabs' ) ][] = $doctor;
				continue;
			}
			foreach ( $terms as $term ) {
				$groups[ $term->name ][] = $doctor;
			}
		}

		ob_start();
		foreach ( $groups as $group_name => $group_doctors ) :
			?>
			<section class="ddt-group-section" aria-label="<?php echo esc_attr( $group_name ); ?>">
				<h3 class="ddt-group-title"><?php echo esc_html( $group_name ); ?></h3>
				<div class="ddt-grid ddt-columns-<?php echo esc_attr( $atts['columns'] ); ?>">
					<?php foreach ( $group_doctors as $doctor ) : ?>
						<?php $this->render_card( $doctor, $atts['image_size'] ); ?>
					<?php endforeach; ?>
				</div>
			</section>
			<?php
		endforeach;
		return ob_get_clean();
	}

	/** Render one doctor card. */
	private function render_card( $doctor, $image_size ) {
		$name        = Doctor_Directory_Tabs_Helpers::doctor_name( $doctor );
		$image_id    = absint( get_post_meta( $doctor->ID, Doctor_Directory_Tabs_Helpers::IMAGE_ID, true ) );
		$designation = get_post_meta( $doctor->ID, Doctor_Directory_Tabs_Helpers::DESIGNATION, true );
		$clinic      = get_post_meta( $doctor->ID, Doctor_Directory_Tabs_Helpers::CLINIC, true );
		$department  = get_post_meta( $doctor->ID, Doctor_Directory_Tabs_Helpers::DEPARTMENT, true );
		$profile     = get_post_meta( $doctor->ID, Doctor_Directory_Tabs_Helpers::PROFILE_LINK, true );
		$specialties = get_the_terms( $doctor->ID, Doctor_Directory_Tabs_Taxonomies::SPECIALTY );
		?>
		<article class="ddt-card">
			<div class="ddt-card-image-wrap">
				<?php if ( $image_id ) : ?>
					<?php echo wp_get_attachment_image( $image_id, $image_size, false, array( 'class' => 'ddt-card-image', 'alt' => $name, 'loading' => 'lazy' ) ); ?>
				<?php else : ?>
					<div class="ddt-card-placeholder" aria-hidden="true">👨‍⚕️</div>
				<?php endif; ?>
				<span class="ddt-card-badge" aria-hidden="true">⚕</span>
			</div>
			<div class="ddt-card-body">
				<h4 class="ddt-card-name"><?php echo esc_html( $name ); ?></h4>
				<?php if ( ! empty( $specialties ) && ! is_wp_error( $specialties ) ) : ?>
					<div class="ddt-card-specialty"><?php echo esc_html( implode( ', ', wp_list_pluck( $specialties, 'name' ) ) ); ?></div>
				<?php endif; ?>
				<?php if ( $designation ) : ?><div class="ddt-card-designation"><?php echo esc_html( $designation ); ?></div><?php endif; ?>
				<?php if ( $clinic || $department ) : ?><div class="ddt-card-clinic"><?php echo esc_html( trim( $clinic . ( $clinic && $department ? ' / ' : '' ) . $department ) ); ?></div><?php endif; ?>
				<?php if ( $profile ) : ?><a class="ddt-profile-link" href="<?php echo esc_url( $profile ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'View profile for %s', 'doctor-directory-tabs' ), $name ) ); ?>"><?php esc_html_e( 'View Profile', 'doctor-directory-tabs' ); ?></a><?php endif; ?>
			</div>
		</article>
		<?php
	}
}
