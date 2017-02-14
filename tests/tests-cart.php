<?php
use \EDD\Tests\UnitTestCase;

/**
 * @group edd_cart
 */
class Test_Cart extends UnitTestCase {

	/**
	 * User ID fixture.
	 *
	 * @access protected
	 * @var    int
	 */
	protected static $user_id;

	/**
	 * Download ID fixture.
	 *
	 * @access protected
	 * @var    int
	 */
	protected static $download_id;

	/**
	 * Discount ID fixture.
	 *
	 * @access protected
	 * @var    int
	 */
	protected static $discount_id;

	/**
	 * WP_Rewrite fixture.
	 *
	 * @access protected
	 * @var    WP_Rewrite
	 */
	protected static $wp_rewrite;

	public static function wpSetUpBeforeClass() {
		self::$user_id = self::edd()->user->create( array(
			'role' => 'administrator'
		) );

		self::$download_id = self::edd()->post->create( array(
			'post_title'  => 'Test Download',
			'post_type'   => 'download',
			'post_status' => 'publish',
		) );

		self::$discount_id =

		self::$wp_rewrite = $GLOBALS['wp_rewrite'];
		self::$wp_rewrite->init();
		flush_rewrite_rules( false );

		edd_add_rewrite_endpoints(self::$wp_rewrite);

		self::_setup_download();
		self::_setup_discount();

	}

	/**
	 * Helper to set up the download meta.
	 *
	 * @access private
	 * @static
	 */
	private static function _setup_download() {
		$_variable_pricing = array(
			array(
				'name' => 'Simple',
				'amount' => 20
			),
			array(
				'name' => 'Advanced',
				'amount' => 100
			)
		);

		$_download_files = array(
			array(
				'name' => 'File 1',
				'file' => 'http://localhost/file1.jpg',
				'condition' => 0
			),
			array(
				'name' => 'File 2',
				'file' => 'http://localhost/file2.jpg',
				'condition' => 'all'
			)
		);

		$meta = array(
			'edd_price' => '0.00',
			'_variable_pricing' => 1,
			'_edd_price_options_mode' => 'on',
			'edd_variable_prices' => array_values( $_variable_pricing ),
			'edd_download_files' => array_values( $_download_files ),
			'_edd_download_limit' => 20,
			'_edd_hide_purchase_link' => 1,
			'edd_product_notes' => 'Purchase Notes',
			'_edd_product_type' => 'default',
			'_edd_download_earnings' => 129.43,
			'_edd_download_sales' => 59,
			'_edd_download_limit_override_1' => 1
		);
		foreach( $meta as $key => $value ) {
			update_post_meta( self::$download_id, $key, $value );
		}
	}

	/**
	 * Helper to set up the discount object.
	 *
	 * @access private
	 * @static
	 */
	private static function _setup_discount() {
		self::$discount_id = edd_store_discount( array(
			'code' => '20OFF',
			'uses' => 54,
			'max' => 10,
			'name' => '20 Percent Off',
			'type' => 'percent',
			'amount' => '20',
			'start' => '12/12/2010 00:00:00',
			'expiration' => '12/31/2050 00:00:00',
			'min_price' => 128,
			'status' => 'active',
			'product_condition' => 'all'
		) );
	}

	public function setUp() {
		parent::setUp();

		wp_set_current_user( self::$user_id );
	}

	public function test_endpoints() {
		$this->assertEquals('edd-add', self::$wp_rewrite->endpoints[0][1]);
		$this->assertEquals('edd-remove', self::$wp_rewrite->endpoints[1][1]);
	}

	public function test_add_to_cart() {
		$options = array(
			'price_id' => 0
		);
		$this->assertEquals( 0, edd_add_to_cart( self::$download_id, $options ) );
	}

	public function test_empty_cart_is_array() {
		$cart_contents = edd_get_cart_contents();

		$this->assertInternalType( 'array', $cart_contents );
		$this->assertEmpty( $cart_contents );
	}

	public function test_add_to_cart_multiple_price_ids_array() {

		$options = array(
			'price_id' => array( 0, 1 )
		);

		edd_add_to_cart( self::$download_id, $options );
		$this->assertEquals( 2, count( edd_get_cart_contents() ) );
	}

	public function test_add_to_cart_multiple_price_ids_array_with_quantity() {
		add_filter( 'edd_item_quantities_enabled', '__return_true' );
		$options = array(
			'price_id' => array( 0, 1 ),
			'quantity' => array( 2, 3 ),
		);

		edd_add_to_cart( self::$download_id, $options );

		$this->assertEquals( 2, count( edd_get_cart_contents() ) );
		$this->assertEquals( 2, edd_get_cart_item_quantity( self::$download_id, array( 'price_id' => 0 ) ) );
		$this->assertEquals( 3, edd_get_cart_item_quantity( self::$download_id, array( 'price_id' => 1 ) ) );
		remove_filter( 'edd_item_quantities_enabled', '__return_true' );
	}

	public function test_add_to_cart_multiple_price_ids_string() {
		$options = array(
			'price_id' => '0,1'
		);
		edd_add_to_cart( self::$download_id, $options );
		$this->assertEquals( 2, count( edd_get_cart_contents() ) );
	}

	public function test_get_cart_contents() {

		$options = array(
			'price_id' => 0
		);
		edd_add_to_cart( self::$download_id, $options );

		$expected = array(
			'0' => array(
				'id' => self::$download_id,
				'options' => array(
					'price_id' => 0
				),
				'quantity' => 1
			)
		);

		$this->assertEquals($expected, edd_get_cart_contents());
	}

	public function test_get_cart_content_details() {

		$options = array(
			'price_id' => 0
		);
		edd_add_to_cart( self::$download_id, $options );

		$expected = array(
			'0' => array(
				'name' => 'Test Download',
				'id' => self::$download_id,
				'item_number' => array(
					'options' => array(
						'price_id' => '0'
					),
					'id' => self::$download_id,
					'quantity' => 1,
				),
				'item_price' => '20.0',
				'quantity' => 1,
				'discount' => '0.0',
				'subtotal' => '20.0',
				'tax' => 0,
				'fees' => array(),
				'price' => '20.0'
			)
		);

		$this->assertEquals( $expected, edd_get_cart_content_details() );

		// Now set a discount and test again
		edd_set_cart_discount( '20OFF' );

		$expected = array(
			'0' => array(
				'name' => 'Test Download',
				'id' => self::$download_id,
				'item_number' => array(
					'options' => array(
						'price_id' => '0'
					),
					'id' => self::$download_id,
					'quantity' => 1,
				),
				'item_price' => '20.0',
				'quantity' => 1,
				'discount' => '4.0',
				'subtotal' => '20.0',
				'tax' => 0,
				'fees' => array(),
				'price' => '16.0'
			)
		);

		$this->assertEquals( $expected, edd_get_cart_content_details() );

		// Now turn on taxes and do it again
		add_filter( 'edd_use_taxes', '__return_true' );
		add_filter( 'edd_tax_rate', function() {
			return 0.20;
		} );

		$expected = array(
			'0' => array(
				'name' => 'Test Download',
				'id' => self::$download_id,
				'item_number' => array(
					'options' => array(
						'price_id' => '0'
					),
					'id' => self::$download_id,
					'quantity' => 1,
				),
				'item_price' => '20.0',
				'quantity' => 1,
				'discount' => '4.0',
				'subtotal' => '20.0',
				'tax' => '3.2',
				'fees' => array(),
				'price' => '19.2'
			)
		);

		$this->assertEquals( $expected, edd_get_cart_content_details() );

		// Now remove the discount code and test with taxes again
		edd_unset_cart_discount( '20OFF' );

		$expected = array(
			'0' => array(
				'name' => 'Test Download',
				'id' => self::$download_id,
				'item_number' => array(
					'options' => array(
						'price_id' => '0'
					),
					'id' => self::$download_id,
					'quantity' => 1,
				),
				'item_price' => '20.0',
				'quantity' => 1,
				'discount' => '0.0',
				'subtotal' => '20.0',
				'tax' => '4.0',
				'fees' => array(),
				'price' => '24.0'
			)
		);

		$this->assertEquals( $expected, edd_get_cart_content_details() );

	}

	public function test_get_cart_item_discounted_amount() {

		// Call without any arguments
		$expected = edd_get_cart_item_discount_amount();
		$this->assertEquals( 0.00, $expected );

		// Call with an array but missing 'id'
		$expected = edd_get_cart_item_discount_amount( array( 'foo' => 'bar' ) );
		$this->assertEquals( 0.00, $expected );

		$options = array(
			'price_id' => 0
		);

		edd_add_to_cart( self::$download_id, $options );

		// Now set a discount and test again
		edd_set_cart_discount( '20OFF' );

		// Test it without a quantity
		$cart_item_args = array( 'id' => self::$download_id );
		$this->assertEquals( 0.00, edd_get_cart_item_discount_amount( $cart_item_args ) );

		// Test it without an options array on an item with variable pricing to make sure we get 0
		$cart_item_args = array( 'id' => self::$download_id, 'quantity' => 1 );
		$this->assertEquals( 0.00, edd_get_cart_item_discount_amount( $cart_item_args ) );

		// Now test it with an options array properly set
		$cart_item_args['options'] = $options;
		$this->assertEquals( 4, edd_get_cart_item_discount_amount( $cart_item_args ) );

		edd_unset_cart_discount( '20OFF' );

	}

	public function test_cart_quantity() {
		$options = array(
			'price_id' => 0
		);
		edd_add_to_cart( self::$download_id, $options );

		$this->assertEquals(1, edd_get_cart_quantity());
	}

	public function test_get_cart_item_quantity() {

 		edd_empty_cart();

		$options = array(
			'price_id' => 0
		);
		edd_add_to_cart( self::$download_id, $options );

		$this->assertEquals( 1, edd_get_cart_item_quantity( self::$download_id, $options ) );

		edd_update_option( 'item_quantities', true );
		// Add the item to the cart again
		edd_add_to_cart( self::$download_id, $options );

		$this->assertEquals( 2, edd_get_cart_item_quantity( self::$download_id, $options ) );
		edd_delete_option( 'item_quantities' );

		// Now add a different price option to the cart
		$options = array(
			'price_id' => 1
		);
		edd_add_to_cart( self::$download_id, $options );

		$this->assertEquals( 1, edd_get_cart_item_quantity( self::$download_id, $options ) );

	}

	public function test_add_to_cart_with_quantities_enabled_on_product() {

		add_filter( 'edd_item_quantities_enabled', '__return_true' );

		$options = array(
			'price_id' => 0,
			'quantity' => 2
		);
		edd_add_to_cart( self::$download_id, $options );

		$this->assertEquals( 2, edd_get_cart_item_quantity( self::$download_id, $options ) );
	}

	public function test_add_to_cart_with_quantities_disabled_on_product() {

		add_filter( 'edd_item_quantities_enabled', '__return_true' );

		update_post_meta( self::$download_id, '_edd_quantities_disabled', 1 );

		$options = array(
			'price_id' => 0,
			'quantity' => 2
		);
		edd_add_to_cart( self::$download_id, $options );

		$this->assertEquals( 1, edd_get_cart_item_quantity( self::$download_id, $options ) );

	}

	public function test_set_cart_item_quantity() {

		edd_update_option( 'item_quantities', true );

		$options = array(
			'price_id' => 0
		);

		edd_add_to_cart( self::$download_id, $options );
		edd_set_cart_item_quantity( self::$download_id, 3, $options );

		$this->assertEquals( 3, edd_get_cart_item_quantity( self::$download_id, $options ) );

		edd_delete_option( 'item_quantities' );

	}

	public function test_item_in_cart() {
		$this->assertFalse(edd_item_in_cart(self::$download_id));
	}

	public function test_cart_item_price() {
		$this->assertEquals( '&#36;0.00' , edd_cart_item_price( 0 ) );
	}

	public function test_get_cart_item_price() {
		$this->assertEquals( false , edd_get_cart_item_price( 0 ) );
	}

	public function test_remove_from_cart() {

		edd_empty_cart();

		edd_add_to_cart( self::$download_id );

		$expected = array();
		$this->assertEquals( $expected, edd_remove_from_cart( 0 ) );
	}

	public function test_set_purchase_session() {
		$this->assertNull( edd_set_purchase_session() );
	}

	public function test_get_purchase_session() {
		$this->assertEmpty( edd_get_purchase_session() );
	}

	public function test_cart_saving_disabled() {
		$this->assertTrue( edd_is_cart_saving_disabled() );
	}

	public function test_is_cart_saved_false() {
		// Test for no saved cart
		$this->assertFalse( edd_is_cart_saved() );

		// Create a saved cart then test again
		$cart = array(
			'0' => array(
				'id' => self::$download_id,
				'options' => array(
					'price_id' => 0
				),
				'quantity' => 1
			)
		);
		update_user_meta( get_current_user_id(), 'edd_saved_cart', $cart );

		edd_update_option( 'enable_cart_saving', '1' );

		$this->assertTrue( edd_is_cart_saved() );
	}

	public function test_restore_cart() {

		// Create a saved cart
		$saved_cart = array(
			'0' => array(
				'id' => self::$download_id,
				'options' => array(
					'price_id' => 0
				),
				'quantity' => 1
			)
		);
		update_user_meta( get_current_user_id(), 'edd_saved_cart', $saved_cart );

		// Set the current cart contents (different from saved)
		$cart = array(
			'0' => array(
				'id' => self::$download_id,
				'options' => array(
					'price_id' => 1
				),
				'quantity' => 1
			)
		);
		EDD()->session->set( 'edd_cart', $cart );
		EDD()->cart->contents = $cart;

		edd_update_option( 'enable_cart_saving', '1' );
		$this->assertTrue( edd_restore_cart() );
		$this->assertEquals( edd_get_cart_contents(), $saved_cart );
	}

	public function test_generate_cart_token() {
		$this->assertInternalType( 'string', edd_generate_cart_token() );
		$this->assertTrue( 32 === strlen( edd_generate_cart_token() ) );
	}

	public function test_edd_get_cart_item_name() {

		edd_add_to_cart( self::$download_id );

		$items = edd_get_cart_content_details();

		$this->assertEquals( 'Test Download - Simple', edd_get_cart_item_name( $items[0] ) );

	}
}
