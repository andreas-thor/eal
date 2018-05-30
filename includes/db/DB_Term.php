<?php

use paslandau\GermanStemmer\GermanStemmer;

use \NlpTools\Tokenizers\WhitespaceTokenizer;
use \NlpTools\Similarity\JaccardIndex;
use \NlpTools\Similarity\CosineSimilarity;
use \NlpTools\Similarity\Simhash;


require_once __DIR__ . '/../external/GermanStemmer.php';
require_once __DIR__ . '/../external/NlpTools/Tokenizers/WhitespaceTokenizer.php';
require_once __DIR__ . '/../external/NlpTools/Tokenizers/TokenizerInterface.php';
require_once __DIR__ . '/../external/NlpTools/Similarity/JaccardIndex.php';




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
			name varchar(200) NOT NULL, 
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
					'name' => $term->name, 
					'parent' => $term->parent,
					'value' => DB_Term::splitAndStem ($term->name . ' ' . $term->description)
				];
			}
		}
		
		$query = "REPLACE INTO " . self::getTableName() . " (id, name, taxonomy, document) VALUES (%d, %s, %s, %s)";

		foreach ($terms as $id => $object) {
			
			$document = $object['value'];
			$parent = $object['parent'];
			while ($parent > 1) {
				$document = $document . ' ' . $terms[$parent]['value'];
				$parent = $terms[$parent]['parent'];
			}
			$wpdb->query( $wpdb->prepare("$query ", [$id, $object['name'], $taxonomy, $document]));
		}
		
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
	
	public static function getMostSimilarTerms (string $text, string $taxonomy, int $numberOfTerms = 3): array {
		
		global $wpdb;
		
		$documents = $wpdb->get_results( sprintf ('SELECT id, name, document FROM %s WHERE taxonomy = \'%s\'',  self::getTableName(), $taxonomy), ARRAY_A); 
		
		$searchTerms = explode (' ', self::splitAndStem($text));	// array of strings
		

		$J = new JaccardIndex();
// 		$cos = new CosineSimilarity();
// 		$simhash = new Simhash(16); // 16 bits hash
		
		$tok = new WhitespaceTokenizer();
		$setA = $tok->tokenize($text);
		
		
		
		$documentDistance = [];
		$documentTermName = [];
		foreach ($documents as $document) {
			
			
			$setB = $tok->tokenize($document['document']);
			
			$simJ = $J->similarity(
				$setA,
				$setB
				);
			
			
		
			
			$documentDistance[$document['id']] = $simJ;
			$documentTermName[$document['id']] = $document['name'];
		}
		
			
/*		
		$documentDistance = [];
		$documentTermName = [];
		foreach ($documents as $document) {
			
			// sum the distance for each doc term
			$docTerms = explode (' ', $document['document']);	// array of strings
			$sumDistance = 0;
			foreach ($docTerms as $s) {
				
				// get the search term with the minimal distance to the document term
				$minDistance = 1;
				foreach ($searchTerms as $t) {
					$minDistance = min($minDistance, levenshtein($s, $t)/(strlen($s)+strlen($t)));
				}
				$sumDistance += $minDistance;
			}
			
			
			$documentDistance[$document['id']] = $sumDistance/count($docTerms);
			$documentTermName[$document['id']] = $document['name'];
			
		}
*/
		// sort by distance; 
		arsort ($documentDistance);
		
		// get the top ($numberOfTerms) term ids
		$result = [];
		$count = 0;
		foreach ($documentDistance as $id => $dist) {
			if ($count == $numberOfTerms) break;
			$result[$id] = $documentTermName[$id];
			$count++;
		}
		
		return $result;
		
		
	}
	
	
}

?>