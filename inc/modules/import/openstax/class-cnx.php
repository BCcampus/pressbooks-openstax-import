<?php
/**
 * Project: pressbooks
 * Project Sponsor: BCcampus <https://bccampus.ca>
 * Copyright 2012-2017 Brad Payne <https://bradpayne.ca>
 * Date: 2017-06-16
 * Licensed under GPLv3, or any later version
 *
 * @author Brad Payne
 * @package Pressbooks_Openstax_Import
 * @license https://www.gnu.org/licenses/gpl-3.0.txt
 * @copyright (c) 2012-2017, Brad Payne
 */

namespace BCcampus\Import\OpenStax;

use \Pressbooks\Modules\Import\Import;


class Cnx extends Import {

	/**
	 * @var \ZipArchive
	 */
	protected $zip;

	/**
	 * @var
	 */
	protected $baseDirectory;

	/**
	 *
	 */
	function __construct() {
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
		}

		$this->zip = new \ZipArchive();
	}

	/**
	 * Mandatory setCurrentImportOption() method, creates WP option 'pressbooks_current_import'
	 *
	 * $upload should look something like:
	 *     Array (
	 *       [file] => /home/dac514/public_html/bdolor/wp-content/uploads/sites/2/2013/04/Hello-World-13662149822.epub
	 *       [url] => http://localhost/~dac514/bdolor/helloworld/wp-content/uploads/sites/2/2013/04/Hello-World-13662149822.epub
	 *       [type] => application/epub+zip
	 *     )
	 *
	 * 'pressbooks_current_import' should look something like:
	 *     Array (
	 *       [file] => '/home/dac514/public_html/bdolor/wp-content/uploads/sites/2/imports/Hello-World-1366214982.epub'
	 *       [file_type] => 'application/epub+zip'
	 *       [type_of] => 'epub'
	 *       [chapters] => Array (
	 *         [some-id] => 'Some title'
	 *         [front-cover] => 'Front Cover'
	 *         [chapter-001] => 'Some other title'
	 *       )
	 *     )
	 *
	 * @see wp_handle_upload
	 *
	 * @param array $upload An associative array of file attributes
	 *
	 * @return bool
	 */
	function setCurrentImportOption( array $upload ) {
		// check that the url is from cnx.org
		$valid_domain = wp_parse_url( $upload['url'] );

		if ( 0 === strcmp( $valid_domain['host'], 'cnx.org' ) && ( 0 === strcmp( $valid_domain['scheme'], 'https' ) ) ) {
			$tmp_file = download_url( $upload['url'], 300 );

			try {
				$this->setValidZip( $tmp_file );
				$collection_contents = $this->parseManifestContent();

			} catch ( \Exception $e ) {
				// delete the file before we go
				unlink( $tmp_file );

				return false;
			}

			$posts = $this->customSort( $collection_contents );

			$option = [
				'file'              => $tmp_file,
				'download_url_file' => $tmp_file,
				'file_type'         => 'application/zip',
				'type_of'           => 'zip',
				'chapters'          => $posts['chapters'],
				'post_types'        => $posts['post_types'],
				'allow_parts'       => true,
			];

			return update_option( 'pressbooks_current_import', $option );

		}

	}

	/**
	 * Original function by Dac Chartrand, modified only slighty
	 *
	 * @param string $xml
	 *
	 * @return \SimpleXMLElement|string
	 * @throws \Exception
	 */
	protected function safetyDance( $xml ) {
		/*
		|--------------------------------------------------------------------------
		| Sanity
		|--------------------------------------------------------------------------
		|
		|
		|
		|
		*/
		libxml_use_internal_errors( true );

		$old_value    = libxml_disable_entity_loader( true );
		$dom          = new \DOMDocument;
		$dom->recover = true; // Try to parse non-well formed documents
		$success      = $dom->loadXML( $xml );
		foreach ( $dom->childNodes as $child ) {
			if ( XML_DOCUMENT_TYPE_NODE === $child->nodeType ) {
				// Invalid XML: Detected use of disallowed DOCTYPE
				$success = false;
				break;
			}
		}
		libxml_disable_entity_loader( $old_value );

		if ( ! $success || isset( $dom->doctype ) ) {
			throw new \Exception( print_r( libxml_get_errors(), true ) );
		}

		$xml = simplexml_import_dom( $dom );
		unset( $dom );

		// halt if loading produces an error
		if ( ! $xml ) {
			throw new \Exception( print_r( libxml_get_errors(), true ) );
		}

		/*
		|--------------------------------------------------------------------------
		| Expected Metadata
		|--------------------------------------------------------------------------
		|
		| Confirm that it's from an expected repo
		|
		|
		*/

		$namespaces = $xml->getDocNamespaces();

		$meta     = $xml->metadata->children( $namespaces['md'] );
		$cnx      = wp_parse_url( $meta->repository, PHP_URL_HOST );
		$expected = [ 'cnx.org', 'legacy.cnx.org' ];

		if ( ! in_array( $cnx, $expected ) ) {
			throw new \Exception( 'The expected CNX repository does not appear to be where this file has been retrieved from' );
		}

		return $xml;
	}

	/**
	 * @return array
	 */
	private function parseManifestMetadata() {

		$collection = $this->getZipContent( $this->baseDirectory . '/' . 'collection.xml', true );
		$xml        = $this->safetyDance( $collection->asXML() );

		/*
		|--------------------------------------------------------------------------
		| Metadata
		|--------------------------------------------------------------------------
		|
		|  check that we're grabbing from the right repo
		|
		|
		*/

		$namespaces = $xml->getDocNamespaces();
		$meta = $xml->metadata->children( $namespaces['md'] );

		// authors
		foreach ( $meta->actors->person as $author ) {
			$authors[] = (string) $author->fullname;
		}

		//organizations
		foreach ( $meta->actors->organization as $org ) {
			$organizations[] = (string) $org->fullname;
		}

		// subjects
		foreach ( $meta->subjectlist as $item ) {
			$subjects[] = (string) $item->subject;
		}

		$metadata = [
			'language'      => (string) $meta->language,
			'title'         => (string) $meta->title,
			'created'       => (string) $meta->created,
			'revised'       => (string) $meta->revised,
			'license'       => (string) $meta->license->attributes()->url,
			'abstract'      => (string) $meta->abstract,
			'keyword'       => (string) $meta->keywordlist->keyword,
			'subject'       => $subjects,
			'authors'       => $authors,
			'organizations' => $organizations,
		];

		return $metadata;

	}

	/**
	 * Checks for valid xml, gets required content from it
	 *
	 * @return array
	 * @throws \Exception
	 */
	private function parseManifestContent() {

		$collection = $this->getZipContent( $this->baseDirectory . '/' . 'collection.xml', true );
		$xml        = $this->safetyDance( $collection->asXML() );

		/*
		|--------------------------------------------------------------------------
		| Content
		|--------------------------------------------------------------------------
		|
		| get parts, chapters and directory names and sequence
		|
		|
		*/
		$namespaces = $xml->getDocNamespaces();

		foreach ( $xml->xpath( '/col:collection/col:content/col:subcollection' ) as $parts ) {
			$part     = $parts->children( $namespaces['md'] );
			$contents = $parts->children( $namespaces['col'] );
			$titles   = $parts->xpath( 'col:content/col:module/md:title' );

			// get chapter titles
			foreach ( $titles as $title ) {
				$title_name[] = (string) $title[0];
			}

			// get  order of directories
			foreach ( $contents->content->module as $content ) {
				$dir_name[] = (string) $content->attributes()->document;
			}

			// put it all together
			$book[] = [
				'part_title'      => (string) $part->title,
				'chapter_titles'  => $title_name,
				'directory_order' => $dir_name,
			];

			// otherwise the array gets loooong
			unset ( $title_name );
			unset ( $dir_name );

		}

		return $book;
	}

	/**
	 * @param array $current_import
	 *
	 * @return bool
	 */
	function import( array $current_import ) {
		// TODO: Implement import() method.
		try {
			$this->setValidZip( $current_import['download_url_file'] );
			$collection_contents = $this->parseManifestContent();
			$metadata            = $this->parseManifestMetadata();

		} catch ( \Exception $e ) {
			// delete the file before we go
			unlink( $current_import['download_url_file'] );

			return false;
		}

		$match_ids      = array_flip( array_keys( $current_import['chapters'] ) );
		$chapter_parent = $this->getChapterParent();

		echo "<pre>";
		print_r( $collection_contents );
		print_r( $metadata );

		echo "</pre>";
		die();
		echo "<pre>";
		print_r( $current_import );
		echo "</pre>";
		die();
	}

	/**
	 * Opens the zip file, set as an instance variable
	 * grabs and sets a directory name
	 *
	 * @param $file_path
	 *
	 * @throws \Exception
	 */
	private function setValidZip( $file_path ) {
		$result = $this->zip->open( $file_path );

		if ( true !== $result ) {
			throw new \Exception( 'Opening CNX file failed' );
		}

		// CNX files always have collection.xml as the first file
		$name      = $this->zip->getNameIndex( 0 );
		$directory = explode( '/', $name, - 1 );

		// set random directory name in an instance variable
		$this->baseDirectory = ( $directory ) ? $directory[0] : '';

		$ok = $this->getZipContent( $this->baseDirectory . '/' . 'collection.xml' );

		if ( ! $ok ) {
			throw new \Exception( 'Bad or corrupted collection.xml' );
		}

	}

	/**
	 * Locates an entry using its name, returns the entry contents
	 *
	 * @param $file
	 * @param bool $as_xml
	 *
	 * @return string|\SimpleXMLElement
	 */
	protected function getZipContent( $file, $as_xml = true ) {

		// Locates an entry using its name
		$index = $this->zip->locateName( urldecode( $file ) );

		if ( false === $index ) {
			return '';
		}

		// returns the contents using its index
		$content = $this->zip->getFromIndex( $index );

		// if it's not xml, return
		if ( ! $as_xml ) {
			return $content;
		}

		return new \SimpleXMLElement( $content );
	}

	/**
	 * Adjust the array to format we need for wp_options
	 *
	 * @param $collection_contents
	 *
	 * @return mixed
	 */
	private function customSort( $collection_contents ) {

		foreach ( $collection_contents as $part ) {
			$option['chapters'][]   = $part['part_title'];
			$option['post_types'][] = 'part';
			foreach ( $part['directory_order'] as $key => $id ) {
				$option['chapters'][ $id ]   = $part['chapter_titles'][ $key ];
				$option['post_types'][ $id ] = 'chapter';
			}
		}

		return $option;

	}

}
