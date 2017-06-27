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
			$tmp_file = download_url( $upload['url'], 600 );

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

		$chapter_parent = $this->getChapterParent();

		foreach ( $current_import['chapters'] as $id => $chapter_title ) {
			if ( ! $this->flaggedForImport( $id ) ) {
				continue;
			}
			try {
				$html = $this->mathTransform( $id );
			} catch ( \Exception $e ) {
				// delete the file before we go
				unlink( $current_import['download_url_file'] );

			}

			$this->kneadAndInsert( $html, $chapter_title, $this->determinePostType( $id ), $chapter_parent, $current_import['default_post_status'] );


			// Done
			return $this->revokeCurrentImport();
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
		$meta       = $xml->metadata->children( $namespaces['md'] );

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
	 * @return boolean|\SimpleXMLElement
	 */
	protected function getZipContent( $file, $as_xml = true ) {

		// Locates an entry using its name
		$index = $this->zip->locateName( urldecode( $file ) );

		if ( false === $index ) {
			return false;
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

	/**
	 * Will apply xsl transformation if mathml detected
	 *
	 * @param $id
	 *
	 * @return mixed|string
	 * @throws \Exception
	 */
	private function mathTransform( $id ) {

		// return string
		$xhtml_string = $this->getZipContent( $this->baseDirectory . '/' . $id . '/' . 'index.cnxml.html', false );

		if ( false === $xhtml_string ) {
			throw new \Exception( 'Required file index.cnxml.html could not be found' );
		}

		libxml_use_internal_errors( true ); // fetch error info

		$filtered_content        = preg_replace( '/\$/', '&#128178;', $xhtml_string );
		$doc                     = new \DOMDocument( '1.0', 'UTF-8' );
		$doc->preserveWhiteSpace = false;
		$doc->recover            = true; // try to parse non-well formed documents

		// loadHTML does not work because mathml entities generate invalid entity errors
		// load XML from a string
		// Disable the ability to load external entities
		$old_value = libxml_disable_entity_loader( true );
		$ok        = $doc->loadXML( $filtered_content, LIBXML_NOBLANKS | LIBXML_NONET | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING );
		libxml_disable_entity_loader( $old_value );

		if ( ! $ok ) {
			error_log( print_r( libxml_get_errors(), true ) );
		}
		libxml_clear_errors();

		$math = $doc->getElementsByTagName( 'math' );
		if ( $math ) {
			// xsl
			$xsl_dom = new \DOMDocument( '1.0', 'UTF-8' );
			$xsl_dom->load( __DIR__ . '/xsl/mmltex.xsl' );

			// configure the transformer
			$proc = new \XSLTProcessor();
			$proc->importStylesheet( $xsl_dom );

			// Here's the transformation that needs to happen
			$xml_string = $proc->transformToXml( $doc->documentElement );

			$clean = $this->cleanHtml( $xml_string );
			$html  = '[latexpage]' . $clean;
		} else {
			$html = $this->cleanHtml( $xhtml_string );
		}

		return $html;
	}

	/**
	 * @param $string
	 *
	 * @return mixed
	 */
	private function cleanHtml( $string ) {
		// some tidying up
		// @TODO more html tidying
		$html_string = preg_replace( '/<\?xml[^>]*>\n/isU', '', $string );
		// put a space between double dollar signs '$$'
		$html_string = preg_replace( '/\${2}/U', '$ $', $html_string );

		$html_string = mb_convert_encoding( $html_string, 'HTML-ENTITIES', 'UTF-8' );

		// latex written like \this needs to be protected by escaping with \\this
		$html_string = addcslashes( $html_string, '\\' );

		// just grab the body element
		preg_match_all( '/(?:<body[^>]*>)(.*)<\/body>/isU', $html_string, $matches, PREG_PATTERN_ORDER );

		return $matches[1][0];
	}

	protected function kneadAndInsert( $html, $title, $post_type, $chapter_parent, $post_status ) {

		$body = $this->tidy( $html );

		$body = $this->kneadHtml( $body );

		$title = wp_strip_all_tags( $title );

		$new_post = [
			'post_title'   => $title,
			'post_content' => $body,
			'post_type'    => $post_type,
			'post_status'  => $post_status,
		];

		if ( 'chapter' === $post_type ) {
			$new_post['post_parent'] = $chapter_parent;
		}

		$pid = wp_insert_post( add_magic_quotes( $new_post ) );

		update_post_meta( $pid, 'pb_show_title', 'on' );
		update_post_meta( $pid, 'pb_export', 'on' );

		Book::consolidatePost( $pid, get_post( $pid ) ); // Reorder
	}

	/**
	 * @param string $html
	 *
	 * @return string
	 */
	protected function tidy( $html ) {

		// Reduce the vulnerability for scripting attacks
		// Make XHTML 1.1 strict using htmlLawed

		$config = [
			'safe'               => 1,
			'valid_xhtml'        => 1,
			'no_deprecated_attr' => 2,
			'hook'               => '\Pressbooks\Sanitize\html5_to_xhtml11',
		];

		return \Pressbooks\HtmLawed::filter( $html, $config );
	}

	/**
	 * @param $html
	 *
	 * @return string
	 */
	protected function kneadHtml( $html ) {
		libxml_use_internal_errors( true );

		// Load HTMl snippet into DOMDocument using UTF-8 hack
		$utf8_hack = '<?xml version="1.0" encoding="UTF-8"?>';
		$doc       = new \DOMDocument();
		$doc->loadHTML( $utf8_hack . $html );

		// Change image paths
		$doc = $this->scrapeAndKneadImages( $doc );

		// If you are storing multi-byte characters in XML, then saving the XML using saveXML() will create problems.
		// Ie. It will spit out the characters converted in encoded format. Instead do the following:
		$html = $doc->saveXML( $doc->documentElement );

		if ( ! $html ) {
			error_log( print_r( libxml_get_errors(), true ) );
		}
		libxml_clear_errors();

		return $html;
	}

	/**
	 * Parse HTML snippet, save all found <img> tags using media_handle_sideload(), return the HTML with changed <img> paths.
	 *
	 * @param \DOMDocument $doc
	 *
	 * @return \DOMDocument
	 */
	protected function scrapeAndKneadImages( \DOMDocument $doc ) {

		$images = $doc->getElementsByTagName( 'img' );

		foreach ( $images as $image ) {
			/** @var \DOMElement $image */
			// Fetch image, change src
			$old_src = $image->getAttribute( 'src' );
			$new_src = $this->fetchAndSaveUniqueImage( $old_src );
			if ( $new_src ) {
				// Replace with new image
				$image->setAttribute( 'src', $new_src );
			} else {
				// Tag broken image
				$image->setAttribute( 'src', "{$old_src}#fixme" );
			}
		}

		return $doc;
	}

	protected function fetchAndSaveUniqueImage( $img_id ) {
		echo "<pre>";
		print_r( get_defined_vars() );
		echo "</pre>";
		die();
	}

}
