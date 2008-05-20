<?php
/**
 * Add a new wiki
 * Wikimedia specific!
 *
 * @file
 * @ingroup Maintenance
 */

$wgNoDBParam = true;

require_once( "commandLine.inc" );
require_once( "rebuildInterwiki.inc" );
require_once( "languages/Names.php" );
if ( count( $args ) != 3 ) {
	wfDie( "Usage: php addwiki.php <language> <site> <dbname>\n" );
}

addWiki( $args[0], $args[1], $args[2] );

# -----------------------------------------------------------------

function addWiki( $lang, $site, $dbName )
{
	global $IP, $wgLanguageNames, $wgDefaultExternalStore;

	$name = $wgLanguageNames[$lang];

	$dbw = wfGetDB( DB_MASTER );
	$common = "/home/wikipedia/common";
	$maintenance = "$IP/maintenance";

	print "Creating database $dbName for $lang.$site\n";
	
	# Set up the database
	$dbw->query( "SET table_type=Innodb" );
	$dbw->query( "CREATE DATABASE $dbName" );
	$dbw->selectDB( $dbName );

	print "Initialising tables\n";
	dbsource( "$maintenance/tables.sql", $dbw );
	dbsource( "$IP/extensions/OAI/update_table.sql", $dbw );
	dbsource( "$IP/extensions/AntiSpoof/mysql/patch-antispoof.sql", $dbw );
	dbsource( "$IP/extensions/CheckUser/cu_changes.sql", $dbw );
	$dbw->query( "INSERT INTO site_stats(ss_row_id) VALUES (1)" );

	# Initialise external storage
	if ( is_array( $wgDefaultExternalStore ) ) {
		$stores = $wgDefaultExternalStore;
	} elseif ( $stores ) {
		$stores = array( $wgDefaultExternalStore );
	} else {
		$stores = array();
	}
	if ( count( $stores ) ) {
		require_once( 'ExternalStoreDB.php' );
		print "Initialising external storage $store...\n";
		global $wgDBuser, $wgDBpassword, $wgExternalServers;
		foreach ( $stores as $storeURL ) {
			$m = array();
			if ( !preg_match( '!^DB://(.*)$!', $storeURL, $m ) ) {
				continue;
			}
			
			$cluster = $m[1];
			
			# Hack
			$wgExternalServers[$cluster][0]['user'] = $wgDBuser;
			$wgExternalServers[$cluster][0]['password'] = $wgDBpassword;
			
			$store = new ExternalStoreDB;
			$extdb =& $store->getMaster( $cluster );
			$extdb->query( "SET table_type=InnoDB" );
			$extdb->query( "CREATE DATABASE $dbName" );
			$extdb->selectDB( $dbName );
			dbsource( "$maintenance/storage/blobs.sql", $extdb );
			$extdb->immediateCommit();
		}
	}

	global $wgTitle, $wgArticle;
	$wgTitle = Title::newMainPage();
	$wgArticle = new Article( $wgTitle );
	$ucsite = ucfirst( $site );

	$wgArticle->insertNewArticle( <<<EOT
==This subdomain is reserved for the creation of a [[wikimedia:Our projects|$ucsite]] in '''[[w:en:{$name}|{$name}]]''' language==

* Please '''do not start editing''' this new site. This site has a test project on the [[incubator:|Wikimedia Incubator]] (or on the [[betawikiversity:|BetaWikiversity]] or on the [[oldwikisource:|Old Wikisource]]) and it will be imported to here.

* If you would like to help translating the interface to this language, please do not translate here, but go to [[betawiki:|Betawiki]], a special wiki for translating the interface. That way everyone can use it on every wiki using the [[mw:|same software]].

* For information about how to edit and for other general help, see [[m:Help:Contents|Help on Wikimedia's Meta-Wiki]] or [[mw:Help:Contents|Help on MediaWiki.org]].

== Sister projects ==
<span class="plainlinks">
[http://www.wikipedia.org Wikipedia] |
[http://www.wiktionary.org Wiktonary] |
[http://www.wikibooks.org Wikibooks] |
[http://www.wikinews.org Wikinews] |
[http://www.wikiquote.org Wikiquote] |
[http://www.wikisource.org Wikisource]
[http://www.wikiversity.org Wikiversity]
</span>

See Wikimedia's [[m:|Meta-Wiki]] for the coordination of these projects.

[[aa:]]
[[af:]]
[[als:]]
[[ar:]]
[[de:]]
[[en:]]
[[as:]]
[[ast:]]
[[ay:]]
[[az:]]
[[bcl:]]
[[be:]]
[[bg:]]
[[bn:]]
[[bo:]]
[[bs:]]
[[cs:]]
[[co:]]
[[cs:]]
[[cy:]]
[[da:]]
[[el:]]
[[eo:]]
[[es:]]
[[et:]]
[[eu:]]
[[fa:]]
[[fi:]]
[[fr:]]
[[fy:]]
[[ga:]]
[[gl:]]
[[gn:]]
[[gu:]]
[[he:]]
[[hi:]]
[[hr:]]
[[hsb:]]
[[hy:]]
[[ia:]]
[[id:]]
[[is:]]
[[it:]]
[[ja:]]
[[ka:]]
[[kk:]]
[[km:]]
[[kn:]]
[[ko:]]
[[ks:]]
[[ku:]]
[[ky:]]
[[la:]]
[[ln:]]
[[lo:]]
[[lt:]]
[[lv:]]
[[hu:]]
[[mi:]]
[[mk:]]
[[ml:]]
[[mn:]]
[[mr:]]
[[ms:]]
[[mt:]]
[[my:]]
[[na:]]
[[nah:]]
[[nds:]]
[[ne:]]
[[nl:]]
[[no:]]
[[oc:]]
[[om:]]
[[pa:]]
[[pl:]]
[[ps:]]
[[pt:]]
[[qu:]]
[[ro:]]
[[ru:]]
[[sa:]]
[[si:]]
[[sk:]]
[[sl:]]
[[sq:]]
[[sr:]]
[[sv:]]
[[sw:]]
[[ta:]]
[[te:]]
[[tg:]]
[[th:]]
[[tk:]]
[[tl:]]
[[tr:]]
[[tt:]]
[[ug:]]
[[uk:]]
[[ur:]]
[[uz:]]
[[vi:]]
[[vo:]]
[[xh:]]
[[yo:]]
[[za:]]
[[zh:]]
[[zu:]]

EOT
, '', false, false );

	print "Adding to dblists\n";

	# Add to dblist
	$file = fopen( "$common/all.dblist", "a" );
	fwrite( $file, "$dbName\n" );
	fclose( $file );

	# Update the sublists
	system("cd $common && ./refresh-dblist");

	print "Constructing interwiki SQL\n";
	# Rebuild interwiki tables
	$sql = getRebuildInterwikiSQL();
	$tempname = tempnam( '/tmp', 'addwiki' );
	$file = fopen( $tempname, 'w' );
	if ( !$file ) {
		wfDie( "Error, unable to open temporary file $tempname\n" );
	}
	fwrite( $file, $sql );
	fclose( $file );
	print "Sourcing interwiki SQL\n";
	dbsource( $tempname, $dbw );
	#unlink( $tempname );
	
	# Create the upload dir
	global $wgUploadDirectory;
	if( file_exists( $wgUploadDirectory ) ) {
		echo "$wgUploadDirectory already exists.\n";
	} else {
		echo "Creating $wgUploadDirectory...\n";
		mkdir( $wgUploadDirectory, 0777 );
		chmod( $wgUploadDirectory, 0777 );
	}

	print "Script ended. You now want to run sync-common-all to publish *dblist files (check them for duplicates first)\n";
}

