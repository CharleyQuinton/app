<?php
/**
 * Adds contest button (RT #38367)
 *
 * @author Bartek Lapinski <bartek@wikia-inc.com>
 */
$wgExtensionCredits['parserhook'][] = array(
                'name' => 'QuickCreate',
                'description' => 'Adds a create new page button with ability to log in for anons',
                'version' => '1,0',
                'author' => array('Bartek Lapinski')
                );

$wgExtensionMessagesFiles['QuickCreate'] = dirname(__FILE__) . '/QuickCreate.i18n.php';

$wgHooks['ParserFirstCallInit'][] = 'wfQuickCreate';

function wfQuickCreate( &$parser ) {
        $parser->setHook( 'quickcreate', 'wfQuickCreateButton' );
        return true;
}

function wfQuickCreateButton( $input, $argv, &$parser ) {
        wfLoadExtensionMessages( 'QuickCreate' );
        global $wgRequest, $wgScript;


	$title = Title::makeTitle( NS_SPECIAL, "CreatePage");
	$link = $title->getFullUrl();
	$onclick = ''; // todo track or not to track? not until feedback
	$output = Xml::openElement( 'a', array(
						'class' => 'wikia_button',
						'id'	=> 'mr-submit',
						'href' => $link,
						'onclick' => $onclick
						 ) )
		.Xml::openElement( 'span' )
		.wfMsg( 'quickcreate' )	
		.Xml::closeElement( 'span' )
		.Xml::closeElement( 'a' );

	return $parser->replaceVariables( $output );	
}

