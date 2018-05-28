<?php

use paslandau\GermanStemmer\GermanStemmer;

require_once __DIR__ . '/../external/GermanStemmer.php';


class DB_Term {
	
	
	private static function getTableName (): string {
		global $wpdb;
		return ($wpdb->prefix) . 'eal_term';
	}
	
	
	public static function createTables() {
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;
		
		/**
		 * minnumber/maxnumber: range of correct answers (relevant for MC only)
		 */
		
		dbDelta (
			"CREATE TABLE " . self::getTableName() . " (
			id bigint(20) unsigned NOT NULL,
			taxonomy varchar(32) NOT NULL,
			document longtext NOT NULL, 
			PRIMARY KEY  (id),
			KEY index_domain (taxonomy)
			) {$wpdb->get_charset_collate()};"
		);
		
		
		
		$wpdb->query("ALTER TABLE " . self::getTableName() . " ADD FULLTEXT INDEX (document)");
		
	}
	
	
	
	
	public static function buildIndex (string $taxonomy) {
		
		mb_internal_encoding("utf-8");
		
		global $wpdb;
		$wpdb->delete( self::getTableName(), array( 'taxonomy' => $taxonomy ) );
		
		
		$a = GermanStemmer::stem("vergnüglich");
		
		$terms = [];
		foreach (get_terms( ['taxonomy' => $taxonomy, 'hide_empty' => false ]) as $term) {
			if ($term instanceof WP_Term) {
				$terms[$term->term_id] = [
					'parent' => $term->parent,
					'value' => DB_Term::splitAndStem ($term->name . ' ' . $term->description)
				];
			}
		}
		
		$query = "REPLACE INTO " . self::getTableName() . " (id, taxonomy, document) VALUES (%d, %s, %s)";

		foreach ($terms as $id => $object) {
			
			$document = $object['value'];
			$parent = $object['parent'];
			while ($parent > 1) {
				$document = $document . ' ' . $terms[$parent]['value'];
				$parent = $terms[$parent]['parent'];
			}
			$wpdb->query( $wpdb->prepare("$query ", [$id, $taxonomy, $document]));
		}
		
		

		
		
		$a=7;
		
	}
	
	private static function splitAndStem (string $text): string {
		
		
		$words = preg_split("/ [^a-zA-Z0-9_ÄÜÖäüöß]+/", $text);	// split by sequence of non-words (words=letters+digits)
		
		$result = [];
		foreach ($words as $word) {
			
			$word = trim($word);
			if (strlen ($word)>2) {
				$result[] = GermanStemmer::stem($word);
			}
		}
		
		return implode(' ', $result);
		
	}
	
}

?>