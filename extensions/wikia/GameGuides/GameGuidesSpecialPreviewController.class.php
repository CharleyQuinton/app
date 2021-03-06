<?php

class GameGuidesSpecialPreviewController extends WikiaSpecialPageController {
	public function __construct() {
		parent::__construct( 'GameGuidesPreview', '', false );
	}

	public function index() {
		if (!$this->wg->User->isAllowed( 'gameguidespreview' )) {
			$this->displayRestrictionError();
			return false;  // skip rendering
		}

		$titleName = $this->getPar();

		//Simple fallback to main page if Title does not exist or none specified
		if( is_null( $titleName ) ) {
				$titleName = Title::newMainPage()->getFullText();
		} else {
			$title = Title::newFromText( $titleName );

			if ( $title instanceof Title && $title->exists() ) {
				$titleName = $title->getFullText();
			} else {
				$titleName = Title::newMainPage()->getFullText();
			}
		}

		// to get global assets
		// http://fallout.jolek.wikia-dev.com/wikia.php?controller=AssetsManager&method=getMultiTypePackage&scripts=gameguides_js&styles=//extensions/wikia/GameGuides/css/GameGuides.scss

		$this->setVal(
			'url',
			GameGuidesController::getUrl(
				'renderFullPage',
				array(
					'allinone' => 1,
					'title' => $titleName
				)
			)
		);
		return true;
	}

}
