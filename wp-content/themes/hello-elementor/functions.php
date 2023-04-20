<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_VERSION', '2.7.1' );

if ( ! isset( $content_width ) ) {
	$content_width = 800; // Pixels.
}

if ( ! function_exists( 'hello_elementor_setup' ) ) {
	/**
	 * Set up theme support.
	 *
	 * @return void
	 */
	function hello_elementor_setup() {
		if ( is_admin() ) {
			hello_maybe_update_theme_version_in_db();
		}

		if ( apply_filters( 'hello_elementor_register_menus', true ) ) {
			register_nav_menus( [ 'menu-1' => esc_html__( 'Header', 'hello-elementor' ) ] );
			register_nav_menus( [ 'menu-2' => esc_html__( 'Footer', 'hello-elementor' ) ] );
		}

		if ( apply_filters( 'hello_elementor_post_type_support', true ) ) {
			add_post_type_support( 'page', 'excerpt' );
		}

		if ( apply_filters( 'hello_elementor_add_theme_support', true ) ) {
			add_theme_support( 'post-thumbnails' );
			add_theme_support( 'automatic-feed-links' );
			add_theme_support( 'title-tag' );
			add_theme_support(
				'html5',
				[
					'search-form',
					'comment-form',
					'comment-list',
					'gallery',
					'caption',
					'script',
					'style',
				]
			);
			add_theme_support(
				'custom-logo',
				[
					'height'      => 100,
					'width'       => 350,
					'flex-height' => true,
					'flex-width'  => true,
				]
			);

			/*
			 * Editor Style.
			 */
			add_editor_style( 'classic-editor.css' );

			/*
			 * Gutenberg wide images.
			 */
			add_theme_support( 'align-wide' );

			/*
			 * WooCommerce.
			 */
			if ( apply_filters( 'hello_elementor_add_woocommerce_support', true ) ) {
				// WooCommerce in general.
				add_theme_support( 'woocommerce' );
				// Enabling WooCommerce product gallery features (are off by default since WC 3.0.0).
				// zoom.
				add_theme_support( 'wc-product-gallery-zoom' );
				// lightbox.
				add_theme_support( 'wc-product-gallery-lightbox' );
				// swipe.
				add_theme_support( 'wc-product-gallery-slider' );
			}
		}
	}
}
add_action( 'after_setup_theme', 'hello_elementor_setup' );

function hello_maybe_update_theme_version_in_db() {
	$theme_version_option_name = 'hello_theme_version';
	// The theme version saved in the database.
	$hello_theme_db_version = get_option( $theme_version_option_name );

	// If the 'hello_theme_version' option does not exist in the DB, or the version needs to be updated, do the update.
	if ( ! $hello_theme_db_version || version_compare( $hello_theme_db_version, HELLO_ELEMENTOR_VERSION, '<' ) ) {
		update_option( $theme_version_option_name, HELLO_ELEMENTOR_VERSION );
	}
}

if ( ! function_exists( 'hello_elementor_scripts_styles' ) ) {
	/**
	 * Theme Scripts & Styles.
	 *
	 * @return void
	 */
	function hello_elementor_scripts_styles() {
		$min_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if ( apply_filters( 'hello_elementor_enqueue_style', true ) ) {
			wp_enqueue_style(
				'hello-elementor',
				get_template_directory_uri() . '/style' . $min_suffix . '.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}

		if ( apply_filters( 'hello_elementor_enqueue_theme_style', true ) ) {
			wp_enqueue_style(
				'hello-elementor-theme-style',
				get_template_directory_uri() . '/theme' . $min_suffix . '.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}
	}
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_scripts_styles' );

if ( ! function_exists( 'hello_elementor_register_elementor_locations' ) ) {
	/**
	 * Register Elementor Locations.
	 *
	 * @param ElementorPro\Modules\ThemeBuilder\Classes\Locations_Manager $elementor_theme_manager theme manager.
	 *
	 * @return void
	 */
	function hello_elementor_register_elementor_locations( $elementor_theme_manager ) {
		if ( apply_filters( 'hello_elementor_register_elementor_locations', true ) ) {
			$elementor_theme_manager->register_all_core_location();
		}
	}
}
add_action( 'elementor/theme/register_locations', 'hello_elementor_register_elementor_locations' );

if ( ! function_exists( 'hello_elementor_content_width' ) ) {
	/**
	 * Set default content width.
	 *
	 * @return void
	 */
	function hello_elementor_content_width() {
		$GLOBALS['content_width'] = apply_filters( 'hello_elementor_content_width', 800 );
	}
}
add_action( 'after_setup_theme', 'hello_elementor_content_width', 0 );

if ( is_admin() ) {
	require get_template_directory() . '/includes/admin-functions.php';
}

/**
 * If Elementor is installed and active, we can load the Elementor-specific Settings & Features
*/

// Allow active/inactive via the Experiments
require get_template_directory() . '/includes/elementor-functions.php';

/**
 * Include customizer registration functions
*/
function hello_register_customizer_functions() {
	if ( is_customize_preview() ) {
		require get_template_directory() . '/includes/customizer-functions.php';
	}
}
add_action( 'init', 'hello_register_customizer_functions' );

if ( ! function_exists( 'hello_elementor_check_hide_title' ) ) {
	/**
	 * Check hide title.
	 *
	 * @param bool $val default value.
	 *
	 * @return bool
	 */
	function hello_elementor_check_hide_title( $val ) {
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			$current_doc = Elementor\Plugin::instance()->documents->get( get_the_ID() );
			if ( $current_doc && 'yes' === $current_doc->get_settings( 'hide_title' ) ) {
				$val = false;
			}
		}
		return $val;
	}
}
add_filter( 'hello_elementor_page_title', 'hello_elementor_check_hide_title' );

/**
 * BC:
 * In v2.7.0 the theme removed the `hello_elementor_body_open()` from `header.php` replacing it with `wp_body_open()`.
 * The following code prevents fatal errors in child themes that still use this function.
 */
if ( ! function_exists( 'hello_elementor_body_open' ) ) {
	function hello_elementor_body_open() {
		wp_body_open();
	}
}

/* my custom code */


/*add_shortcode( 'woo_cart', 'mysite_cart_count' );

function mysite_cart_count() {
    global $woocommerce;
    $cart_count = $woocommerce->cart->cart_contents_count;
    if ($cart_count == 0) {
        return '<a class="cart-contents" href="' . esc_url(wc_get_cart_url()) . '" title="' . esc_attr__('View your shopping cart') . '"><i class="fa fa-shopping-bag"></i></a>';
    } else {
        return '<a class="cart-contents" href="' . esc_url(wc_get_cart_url()) . '" title="' . esc_attr__('View your shopping cart') . '"><i class="fa fa-shopping-bag"></i> <span class="cart-count product-counter">' . esc_html($cart_count) . '</span></a>';
    }
} */


function get_cart_count() {
	//Check if WooCommerce is active
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		global $woocommerce;

		$shop_page_url = get_permalink( woocommerce_get_page_id( 'shop' ) );
		$cart_contents_count = $woocommerce->cart->cart_contents_count;
		$cart_contents = sprintf(_n('%d item', '%d items', $cart_contents_count, 'FreshySites'), $cart_contents_count);
	  	
	  	// link for cart with icon
	  	$the_cart_data = '<a class="fs-cart-contents" href="/cart" title="View your shopping cart"><i class="fa fa-shopping-bag"></i>';
		// If more than one item in the cart, then show the qty and cost
		if ( $cart_contents_count > 0 ) {
			$the_cart_data .= ' <span class="cart-count product-counter">'. $cart_contents_count .'</span>';
		}
	  	// close the cart link
		$the_cart_data .= '</a>';
	  
		return $the_cart_data;
	}
}
add_shortcode( 'woo_cart', 'get_cart_count' );


/**
 * Ensure cart contents update when products are added to the cart via AJAX
 * We essentially duplicated what we added above
 */
function my_header_add_to_cart_fragment( $fragments ) {
 
	ob_start();

	
	$cart_contents_count = WC()->cart->cart_contents_count;
	$cart_contents = sprintf(_n('%d item', '%d items', $cart_contents_count, 'FreshySites'), $cart_contents_count);

	?><a class="fs-cart-contents" href="/cart" title="View your shopping cart"><i class="fa fa-shopping-bag"></i><?php
	if ( $cart_contents_count > 0 ) {
	?>
		<span class="cart-count product-counter"><?php echo $cart_contents_count; ?></span>
		<?php            
	}
	?></a><?php
 
	$fragments['a.fs-cart-contents'] = ob_get_clean();
     
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'my_header_add_to_cart_fragment' );



add_filter( 'the_title', 'shorten_woo_product_title', 10, 2 );
function shorten_woo_product_title( $title, $id ) {
    if (! is_singular( array( 'product' ) ) && get_post_type( $id ) === 'product') {
        return wp_trim_words( $title, 5 ); // change last number to the number of words you want
    } else {
        return $title;
    }
}



add_action( 'woocommerce_cart_calculate_fees', 'add_shipping_charge' );
 
function add_shipping_charge() {
    global $woocommerce;
 
    // Set the minimum order amount for the shipping charge
    $minimum_order_amount = 50;
 
    // Set the shipping charge amount
    $shipping_charge = 5.99;
 
    // Get the cart total
    $cart_total = $woocommerce->cart->cart_contents_total;
 
    // Check if the cart total is less than the minimum order amount
    if ( $cart_total < $minimum_order_amount ) {
        // Add the shipping charge
        $woocommerce->cart->add_fee( 'Shipping Charges (On Orders Less Than £50 ) ', $shipping_charge );
    }
}
