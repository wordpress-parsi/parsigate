<?php
/**
 * Isolated guard tests for ParsiGate 1.1.1 private CardToCard receipt flow.
 *
 * @package ParsiGate\Tests
 *
 * These deliberately do not claim to exercise HTTP uploads, MySQL advisory lock
 * concurrency, persisted order updates or filesystem rollback. Those require
 * the separate two-lab integration suite.
 */

use PHPUnit\Framework\TestCase;

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound -- This isolated test intentionally groups minimal stub classes with its test case.

if ( ! defined( 'DB_NAME' ) ) {
	define( 'DB_NAME', 'pg9_isolated_tests' );
}

/**
 * Test-only exception used to intercept wp_die() without stopping PHPUnit.
 */
class PG9_Stop extends \RuntimeException {

	/**
	 * HTTP status captured from wp_die().
	 *
	 * @var int
	 */
	public $http_status;
	/**
	 * Initialize the isolated exception or handler stub.
	 *
	 * @param mixed $message Exception message used only in this test.
	 * @param int $status Captured HTTP status.
	 *
	 * @return void
	 */
	public function __construct( $message, $status ) {
		parent::__construct( (string) $message );
		$this->http_status = (int) $status;
	}
}

/**
 * Minimal mutable WooCommerce order stub for isolated receipt guard tests.
 */
class PG9_Order {

	/**
	 * Simulated WooCommerce order status.
	 *
	 * @var string
	 */
	public $status    = 'pending';
	/**
	 * Simulated payment method ID.
	 *
	 * @var string
	 */
	public $method    = 'cardtocard';
	/**
	 * Simulated saved attachment ID.
	 *
	 * @var int
	 */
	public $receipt   = 0;

	/** @var string Protected private receipt filename. */
	public $protected_receipt = '';
	/**
	 * Customer ID owning the simulated order.
	 *
	 * @var int
	 */
	public $owner     = 0;
	/**
	 * Simulated guest order key.
	 *
	 * @var string
	 */
	public $order_key = 'pg9-valid-key';

	/**
	 * Return the simulated order payment method.
	 *
	 * @return string
	 */
	public function get_payment_method() {
		return $this->method; }
	/**
	 * Return the simulated customer ID.
	 *
	 * @return int
	 */
	public function get_user_id() {
		return $this->owner; }
	/**
	 * Return the simulated guest order key.
	 *
	 * @return string
	 */
	public function get_order_key() {
		return $this->order_key; }
	/**
	 * Check whether the simulated order has one of the supplied statuses.
	 *
	 * @param string[] $statuses List of acceptable WooCommerce statuses.
	 *
	 * @return bool
	 */
	public function has_status( $statuses ) {
		return in_array( $this->status, (array) $statuses, true ); }
	/**
	 * Return the simulated receipt attachment meta.
	 *
	 * @param string $name Meta key to retrieve.
	 * @param bool $single Whether to return a single value.
	 *
	 * @return mixed
	 */
	public function get_meta( $name, $single = true ) {
		if ('_cardtocard_receipt_image_id' === $name) {
            return $this->receipt;
        }
        return '_cardtocard_receipt_file' === $name ? $this->protected_receipt : null;
	}
}

/**
 * Database stub that counts MySQL named-lock operations without connecting.
 */
class PG9_DB {

	/**
	 * Simulated WordPress table prefix.
	 *
	 * @var string
	 */
	public $prefix         = 'pg9_';
	/**
	 * Number of advisory lock acquisition attempts.
	 *
	 * @var int
	 */
	public $acquires       = 0;
	/**
	 * Number of advisory lock release attempts.
	 *
	 * @var int
	 */
	public $releases       = 0;
	/**
	 * Whether named-lock acquisition is successful.
	 *
	 * @var bool
	 */
	public $lock_available = true;
	/**
	 * Substitute a parameter in the test-only SQL statement.
	 *
	 * @param string $query SQL statement containing a placeholder.
	 * @param mixed $value Value substituted into the placeholder.
	 *
	 * @return string
	 */
	public function prepare( $query, $value ) {
		return str_replace( '%s', "'" . str_replace( "'", "''", (string) $value ) . "'", $query );
	}
	/**
	 * Simulate named-lock SQL calls and count them.
	 *
	 * @param string $query Simulated SQL query.
	 *
	 * @return string
	 * @throws \RuntimeException When unexpected SQL reaches this test stub.
	 */
	public function get_var( $query ) {
		if ( false !== strpos( $query, 'GET_LOCK(' ) ) {
			++$this->acquires;
			return $this->lock_available ? '1' : '0';
		}
		if ( false !== strpos( $query, 'RELEASE_LOCK(' ) ) {
			++$this->releases;
			return '1';
		}
		throw new \RuntimeException( 'Unexpected SQL in isolated guard test.' );
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Stub WordPress filter registration and record the filter name.
	 *
	 * @param string $name Filter name to record.
	 * @param callable $callback Registered callback, unused by this stub.
	 * @param int $priority Hook priority, unused by this stub.
	 * @param int $accepted_args Accepted argument count, unused by this stub.
	 *
	 * @return bool
	 */
	function add_filter( $name, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['pg9_registered_filters'][] = $name;
		return true;
	}
}
if ( ! function_exists( 'add_action' ) ) {
	/**
	 * Stub WordPress action registration.
	 *
	 * @param string $name Hook name, unused by this stub.
	 * @param callable $callback Registered callback, unused by this stub.
	 * @param int $priority Hook priority, unused by this stub.
	 * @param int $accepted_args Accepted argument count, unused by this stub.
	 *
	 * @return bool
	 */
	function add_action( $name, $callback, $priority = 10, $accepted_args = 1 ) {
		return true; }
}
if ( ! function_exists( 'absint' ) ) {
	/**
	 * Stub WordPress absolute-integer conversion.
	 *
	 * @param mixed $value Input value.
	 *
	 * @return int
	 */
	function absint( $value ) {
		return abs( (int) $value ); }
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Stub WordPress field sanitization for static fixture strings.
	 *
	 * @param mixed $value Input fixture string.
	 *
	 * @return string
	 */
	function sanitize_text_field( $value ) {
		return (string) $value; }
}
if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * Stub WordPress unslashing of fixture values.
	 *
	 * @param mixed $value Input fixture value.
	 *
	 * @return mixed
	 */
	function wp_unslash( $value ) {
		return $value; }
}
if ( ! function_exists( 'wp_verify_nonce' ) ) {
	/**
	 * Accept only the expected synthetic nonce and action.
	 *
	 * @param string $nonce Synthetic request nonce.
	 * @param string $action Nonce action name.
	 *
	 * @return bool
	 */
	function wp_verify_nonce( $nonce, $action ) {
		return 'pg9-valid-nonce' === $nonce && 0 === strpos( (string) $action, 'parsigate_cardtocard_upload_receipt_' );
	}
}
if ( ! function_exists( 'is_user_logged_in' ) ) {
	/**
	 * Return the simulated authentication state.
	 *
	 * @return bool
	 */
	function is_user_logged_in() {
		return (bool) $GLOBALS['pg9_logged_in']; }
}
if ( ! function_exists( 'get_current_user_id' ) ) {
	/**
	 * Return the simulated logged-in customer ID.
	 *
	 * @return int
	 */
	function get_current_user_id() {
		return (int) $GLOBALS['pg9_current_user']; }
}
if ( ! function_exists( 'wc_clean' ) ) {
	/**
	 * Return the test fixture value without modifying it.
	 *
	 * @param mixed $value Fixture value.
	 *
	 * @return string
	 */
	function wc_clean( $value ) {
		return (string) $value; }
}
if ( ! function_exists( 'wc_get_order' ) ) {
	/**
	 * Reload the simulated order and optionally inject a failure.
	 *
	 * @param int $id Order ID to retrieve.
	 *
	 * @return mixed
	 * @throws \RuntimeException When the synthetic reload failure is requested.
	 */
	function wc_get_order( $id ) {
		++$GLOBALS['pg9_fetches'];
		if ( 2 === $GLOBALS['pg9_fetches'] && $GLOBALS['pg9_throw_on_reload'] ) {
			throw new \RuntimeException( 'Injected reload exception' );
		}
		if ( 2 === $GLOBALS['pg9_fetches'] && null !== $GLOBALS['pg9_reload_status'] ) {
			$GLOBALS['pg9_order']->status = $GLOBALS['pg9_reload_status'];
		}
		return 923 === (int) $id ? $GLOBALS['pg9_order'] : false;
	}
}
if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * Stub translation for test-only messages.
	 *
	 * @param string $value Message to translate.
	 * @param string $domain Text domain, unused by this stub.
	 *
	 * @return string
	 */
	function esc_html__( $value, $domain = null ) {
		return (string) $value; }
}
if ( ! function_exists( 'wp_die' ) ) {
	/**
	 * Replace WordPress request termination with a test exception.
	 *
	 * @param string $message Request termination message.
	 * @param mixed $title Optional title or numeric status.
	 * @param array $args Optional response arguments.
	 *
	 * @return void
	 * @throws PG9_Stop When the stub intercepts a request termination.
	 */
	function wp_die( $message = '', $title = '', $args = array() ) {
		$code = is_int( $title ) ? $title : ( isset( $args['response'] ) ? (int) $args['response'] : 500 );
		throw new PG9_Stop( $message, $code );
	}
}

require_once dirname( __DIR__, 2 ) . '/inc/compatibility/Base.php';
require_once dirname( __DIR__, 2 ) . '/inc/compatibility/CardToCard.php';

/**
 * Isolated request validation tests for the actual CardToCard handler.
 */
final class CardToCardReceiptGuardTest extends TestCase {

	/**
	 * Real CardToCard handler instance for the isolated test.
	 *
	 * @var \ParsiGate\compatibility\CardToCard
	 */
	private $handler;

	/**
	 * Initialize the request superglobals and the fake order and DB.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$GLOBALS['pg9_registered_filters'] = array();
		$GLOBALS['wpdb']                   = new PG9_DB();
		$GLOBALS['pg9_order']              = new PG9_Order();
		$GLOBALS['pg9_fetches']            = 0;
		$GLOBALS['pg9_logged_in']          = false;
		$GLOBALS['pg9_current_user']       = 0;
		$GLOBALS['pg9_throw_on_reload']    = false;
		$GLOBALS['pg9_reload_status']      = null;
		$_GET                              = array(
			'parsigate_cardtocard_upload' => '1',
			'key'                         => 'pg9-valid-key',
		);
		$_POST                             = array(
			'parsigate_cardtocard_order_id' => '923',
			'parsigate_cardtocard_nonce'    => 'pg9-valid-nonce',
		);
		$_FILES                            = array();
		$this->handler                     = new \ParsiGate\compatibility\CardToCard();
	}

	/**
	 * Reset synthetic request superglobals after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$_GET   = array();
		$_POST  = array();
		$_FILES = array();
	}

	/**
	 * Assert the expected rejection status and lock counts.
	 *
	 * @param int|null $status Expected HTTP status, or null for any error status.
	 * @param int $acquires Expected number of lock acquisitions.
	 * @param int $releases Expected number of lock releases.
	 *
	 * @return void
	 */
	private function expectRejection( $status, $acquires, $releases ) {
		try {
			$this->handler->handle_upload();
			$this->fail( 'Expected upload rejection.' );
		} catch ( PG9_Stop $e ) {
			if ( null !== $status ) {
				$this->assertSame( $status, $e->http_status );
			} else {
				$this->assertGreaterThanOrEqual( 400, $e->http_status );
			}
			$this->assertSame( $acquires, $GLOBALS['wpdb']->acquires );
			$this->assertSame( $releases, $GLOBALS['wpdb']->releases );
		}
	}

	/**
	 * Ignore requests that do not target receipt upload.
	 *
	 * @return void
	 */
	public function test_nonupload_request_has_no_effect(): void {
		unset( $_GET['parsigate_cardtocard_upload'] );
		$this->assertNull( $this->handler->handle_upload() );
		$this->assertSame( 0, $GLOBALS['wpdb']->acquires );
	}

	/**
	 * Reject a request with an invalid nonce before acquiring a lock.
	 *
	 * @return void
	 */
	public function test_invalid_nonce_is_rejected_before_lock(): void {
		$_POST['parsigate_cardtocard_nonce'] = 'invalid';
		$this->expectRejection( null, 0, 0 );
	}

	/**
	 * Reject a guest with an invalid order key before acquiring a lock.
	 *
	 * @return void
	 */
	public function test_wrong_guest_key_is_rejected_before_lock(): void {
		$_GET['key'] = 'invalid';
		$this->expectRejection( 403, 0, 0 );
	}

	/**
	 * Reject a logged-in user who does not own the order.
	 *
	 * @return void
	 */
	public function test_unrelated_logged_in_user_is_rejected_before_lock(): void {
		$GLOBALS['pg9_logged_in']    = true;
		$GLOBALS['pg9_current_user'] = 9;
		$GLOBALS['pg9_order']->owner = 10;
		$this->expectRejection( 403, 0, 0 );
	}

	/**
	 * Reject uploads for orders using a different gateway.
	 *
	 * @return void
	 */
	public function test_other_payment_method_is_rejected_before_lock(): void {
		$GLOBALS['pg9_order']->method = 'cod';
		$this->expectRejection( null, 0, 0 );
	}

	/**
	 * Reject requests for nonexistent orders.
	 *
	 * @return void
	 */
	public function test_missing_order_is_rejected_before_lock(): void {
		$_POST['parsigate_cardtocard_order_id'] = '999';
		$this->expectRejection( null, 0, 0 );
	}

	/**
	 * Return HTTP 409 when the named lock is unavailable.
	 *
	 * @return void
	 */
	public function test_lock_contention_fails_closed(): void {
		$GLOBALS['wpdb']->lock_available = false;
		$this->expectRejection( 409, 1, 0 );
	}

	/**
	 * Reject nonpending orders and release the acquired lock.
	 *
	 * @return void
	 */
	public function test_nonpending_statuses_are_rejected_and_lock_released(): void {
		foreach ( array( 'on-hold', 'cancelled', 'processing', 'completed' ) as $status ) {
			$GLOBALS['wpdb']              = new PG9_DB();
			$GLOBALS['pg9_fetches']       = 0;
			$GLOBALS['pg9_order']->status = $status;
			$this->expectRejection( 409, 1, 1 );
		}
	}

	/**
	 * Reject an attempt to replace an existing receipt.
	 *
	 * @return void
	 */
	public function test_existing_receipt_is_not_replaced(): void {
		$GLOBALS['pg9_order']->receipt = 15;
		$this->expectRejection( 409, 1, 1 );
	}

	/**
     * A receipt recorded in the new protected storage must block re-upload.
     *
     * @return void
     */
    public function test_existing_private_receipt_is_not_replaced(): void {
        $GLOBALS['pg9_order']->protected_receipt = 'ABC12345678901234567890123456789.png';
        $this->expectRejection(409, 1, 1);
    }

	/**
	 * Recheck order status after taking the advisory lock.
	 *
	 * @return void
	 */
	public function test_reloaded_status_is_checked_under_lock(): void {
		$GLOBALS['pg9_reload_status'] = 'on-hold';
		$this->expectRejection( 409, 1, 1 );
		$this->assertSame( 2, $GLOBALS['pg9_fetches'] );
	}

	/**
	 * Always release the lock when order reload throws.
	 *
	 * @return void
	 */
	public function test_unexpected_reload_exception_releases_lock(): void {
		$GLOBALS['pg9_throw_on_reload'] = true;
		try {
			$this->handler->handle_upload();
			$this->fail( 'Expected injected read error.' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'Injected reload exception', $e->getMessage() );
			$this->assertSame( 1, $GLOBALS['wpdb']->acquires );
			$this->assertSame( 1, $GLOBALS['wpdb']->releases );
		}
	}

	/**
	 * Do not register the previous payment-complete status filter.
	 *
	 * @return void
	 */
	public function test_payment_complete_status_filter_is_not_registered(): void {
		$this->assertNotContains( 'woocommerce_payment_complete_order_status', $GLOBALS['pg9_registered_filters'] );
		$this->assertContains( 'parsigate_gateway_set_completed', $GLOBALS['pg9_registered_filters'] );
	}
}
