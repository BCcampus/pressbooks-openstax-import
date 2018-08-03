<?php
/**
 * Class CnxTest
 *
 * @package Pressbooks_Openstax_Import
 */

/**
 * Sample test case.
 */
class CnxTest extends WP_UnitTestCase {

	protected $cnx;

	public function setUp() {
		parent::setUp();
		//$this->cnx = new \BCcampus\Import\OpenStax\Cnx();
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * A single example test.
	 */
	function test_extractLicense() {
		//      $uri = 'https://somedomainhere.ca';
		//      $this->cnx->extractLicense( $uri );
		$this->assertTrue( true );

	}
}
