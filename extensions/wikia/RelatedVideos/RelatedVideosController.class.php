<?php

class RelatedVideosController extends WikiaController {

	const SURVEY_URL = 'http://www.surveymonkey.com/s/RelatedVideosExperience';
	public function __construct( WikiaApp $app ) {
		global $wgRelatedVideosOnRail;
		$this->app = $app;
		if( !empty( $wgRelatedVideosOnRail ) ) {
			RelatedVideosService::$width = 150;
			RelatedVideosService::$height = 90;
		}
	}

	public function getCaruselRL(){
		// just use different template, logic stays the same
		return $this->getCarusel();
	}

	public function getCaruselElementRL(){
		$this->response->setTemplateEngine(WikiaResponse::TEMPLATE_ENGINE_MUSTACHE);

		// just use different template, logic stays the same
		return $this->getCaruselElement();
	}

	public function getCarusel(){
		if( $this->app->checkSkin( 'wikiamobile' ) || Wikia::isMainPage() || ( !$this->app->wg->title instanceof Title ) || !$this->app->wg->title->exists() ) {
			return false;
		}

		$rvs = new RelatedVideosService();
		$videos = $rvs->getRVforArticleId( $this->app->wg->title->getArticleId() );

		$this->linkToSeeMore = !empty($this->app->wg->EnableSpecialVideosExt) ? SpecialPage::getTitleFor("Videos")->escapeLocalUrl() : Title::newFromText(WikiaVideoPage::getVideosCategory())->getFullUrl();
		$this->videos = $videos;
		$this->totalVideos = $this->getTotalVideos();
		$this->canAddVideo = $this->wg->User->isAllowed( 'relatedvideosedit' );
	}
	
	public function getTotalVideos(){
		$mediaService = new MediaQueryService();
		return $this->wg->Lang->formatNum( $mediaService->getTotalVideos() );	
	}

	public function getVideo(){
		/* this is only used pre-migration (before Video Refactoring)
		   afterwards videos are played using regular Lightbox
		*/

		$title = urldecode($this->getVal( 'video_title' ));
		$external = $this->getVal( 'external', '' );
		$external = empty( $external ) ? null : $this->app->wg->wikiaVideoRepoDBName;
		$cityShort = $this->getVal('cityShort');
		$videoHeight = $this->getVal('videoHeight');
		$controlerName = str_replace('Controller', '', $this->getVal('controlerName', 'RelatedVideos'));
		$wikiLink = $this->getVal('wikiLink', '');

		$oRelatedVideosService = F::build('RelatedVideosService');
		$result = $oRelatedVideosService->getRelatedVideoDataFromTitle( array( 'title' => $title, 'source' => $external ), RelatedVideosData::DEFAULT_OASIS_VIDEO_WIDTH, $cityShort, $videoHeight );
		if ( isset( $result['error'] ) ){
			$this->setVal( 'error', $result['error'] );
		} else {
			$this->setVal( 'width', intval( $result['thumbnailData']['width'] ) );
			$this->setVal( 'height', intval( $result['thumbnailData']['height'] ) );
			$this->setVal( 'json', $result['embedJSON'] );
			if ( !empty( $result['embedJSON'] ) && isset( $result['embedJSON']['id'] ) ){
				$videoHtml = '<div id="'.$result['embedJSON']['id'].'"></div>';
			} else {
				$videoHtml = $result['embedCode'];
			}
			$this->setVal( 'html',
				 $this->app->renderView(
					$controlerName,
					'getVideoHtml',
					array(
						'videoHtml' => $videoHtml,
						'embedUrl' => $result['fullUrl'],
						'wikiLink' => $wikiLink,
					)
				)
			);
			$this->setVal( 'title', $result['title'] );
			if ( !empty( $result['external'] ) ){
				$this->setVal( 'embedUrl', $result['fullUrl'] );
			}
		}
	}

	public function getVideoHtml(){

		$videoHtml = $this->getVal( 'videoHtml' );
		$embedUrl = $this->getVal( 'embedUrl' );

		$this->setVal( 'videoHtml', $videoHtml );
		$this->setVal( 'embedUrl', $embedUrl );
	}

	/*
	 * get data for an article stored in NS_RELATED_VIDEOS
	 */
	public function getLists() {

		$titleStr = $this->request->getVal( 'title', null );
		$title = Title::newFromText( $titleStr, NS_RELATED_VIDEOS );
		$relatedVideosNSData = RelatedVideosNamespaceData::newFromTitle($title);
		$this->setVal( 'data', $relatedVideosNSData->getData() );
	}

	/*
	 * for getting videos locally and cross wiki
	 */
	public function getVideoData() {

		$videoArticleId = $this->getVal( 'articleId', 0 );
		$videoName = urldecode($this->getVal( 'title', '' ));
		$width = $this->getVal( 'width', 0 );
		$useMaster = $this->getVal( 'useMaster', 0 );
		$videoWidth = $this->getVal( 'videoWidth', RelatedVideosData::DEFAULT_OASIS_VIDEO_WIDTH );
		$videoHeight = $this->getVal( 'videoHeight', '' );
		$cityShort = $this->getVal( 'cityShort', 'life');
		$useJWPlayer = $this->getVal( 'useJWPlayer', true );
		$autoplay = $this->getVal( 'autoplay', true );
		$inAjaxReponse = $this->getVal('inAjaxResponse');

		if ( $videoArticleId ) {
			$videoTitle = Title::newFromID( $videoArticleId, Title::GAID_FOR_UPDATE );
			$useMaster = true;
		} else {
			$videoTitle = Title::newFromText( $videoName, NS_VIDEO );
			// var_dump( $videoTitle );
			$useMaster = ( false || !empty( $useMaster ) );
		}

		$rvd = F::build('RelatedVideosData'); /* @var $rvd RelatedVideosData */
		$videoData = $rvd->getVideoData( $videoName, $width, $videoWidth, $autoplay, $useMaster, $cityShort, $videoHeight, $useJWPlayer, $inAjaxReponse );
		$this->setVal( 'data', $videoData );
	}

	public function getCaruselElement() {
		wfProfileIn(__METHOD__);

		$video = $this->getVal( 'video' );

		if( empty( $video ) ) {
			$title = $this->getVal('videoTitle');
			$rvs = F::build('RelatedVideosService');
			$video = $rvs->getRelatedVideoDataFromTitle( array( 'title' => $title ) );
		}

 		$preloaded = $this->getVal( 'preloaded' );

		$videoTitle = F::build('Title', array($video['id'], NS_FILE), 'newFromText');
		$videoFile = wfFindFile($videoTitle);

		if( $videoFile ) {
			$videoThumbObj = $videoFile->transform( array('width'=>$video['thumbnailData']['width'],
														  'height'=>$video['thumbnailData']['height']) );
			$videoThumb = $videoThumbObj->toHtml(
				array(
					'custom-url-link' => $video['fullUrl'],
					'linkAttribs' => array(
						'class' => 'video-thumbnail lightbox',
						'data-video-name' => $video['title'],
						'data-external' => $video['external'],
						'data-ref' => $video['prefixedUrl']
					),
					'duration' => true,
					'src' => $preloaded ? false : wfBlankImgUrl(),
					'constHeight' => RelatedVideosService::$height,
					'usePreloading' => true,
					'disableRDF' => true
				)
			);

			$video['views'] = MediaQueryService::getTotalVideoViewsByTitle( $videoTitle->getDBKey() );

			// Add ellipses if title is too long
			$maxDescriptionLength = 45;
			$video['truncatedTitle'] = ( strlen( $video['title'] ) > $maxDescriptionLength )
				? substr( $video['title'], 0, $maxDescriptionLength).'&#8230;'
				: $video['title'];

			$video['viewsMsg'] = wfMsg('related-videos-video-views', $this->wg->ContLang->formatNum($video['views']));

			$userGroups = $this->wg->User->getEffectiveGroups();
			$isAdmin = in_array('admin', $userGroups) || in_array('staff', $userGroups);

			$this->removeTooltip = wfMsg('related-videos-tooltip-remove');
			$this->videoThumb = $videoThumb;
			$this->video = $video;
			$this->preloaded = $preloaded;
			$this->isAdmin = $isAdmin;
			$this->totalVideos = $this->getTotalVideos();

		} else {
			Wikia::log(__METHOD__, false, 'A video file not found. ID: '.$video['id']);
		}

		// set cache control to 1 day
		$this->response->setCacheValidity(86400, 86400, array(WikiaResponse::CACHE_TARGET_BROWSER, WikiaResponse::CACHE_TARGET_VARNISH));

		wfProfileOut(__METHOD__);
	}

	public function addVideo() {
		global $wgRelatedVideosOnRail;

		if ( !$this->wg->User->isLoggedIn() ) {
			$this->error = $this->wf->Msg( 'videos-error-not-logged-in' );
			return;
		}

		if ( !$this->wg->User->isAllowed( 'relatedvideosedit' ) ) {
			$this->error = $this->wf->Msg( 'related-videos-add-video-error-permission-video' );
			return;
		}

		$url = urldecode( $this->getVal( 'url', '' ) );
		if ( empty( $url ) ) {
			$this->error = $this->wf->Msg( 'videos-error-no-video-url' );
			return;
		}

		if ( $this->wg->User->isBlocked() ) {
			$this->error = $this->wf->Msg( 'videos-error-blocked-user' );
			return;
		}

		$articleId = $this->getVal( 'articleId', '' );
		$rvd = F::build( 'RelatedVideosData' ); /** @var $rvd RelatedVideosData */
		$retval = $rvd->addVideo( $articleId, $url );
		if ( is_array( $retval ) ) {
			$rvs = F::build( 'RelatedVideosService' ); /** @var $rvs RelatedVideosService */
			$data = $rvs->getRelatedVideoDataFromMaster( $retval );
			if ( empty($wgRelatedVideosOnRail) ) {
				$this->setVal( 'html', $this->app->renderView( 'RelatedVideos', 'getCaruselElement', array( 'video' => $data, 'preloaded' => 1 ) ));
			} else {
				$this->setVal( 'html', $this->app->renderView( 'RelatedVideos', 'getCaruselElementRL', array( 'video' => $data, 'preloaded' => 1 ) ));
			}
			$this->setVal( 'error', isset( $data['error'] ) ? $data['error'] : null);
		} else {
			$this->setVal( 'data', null );
			$this->setVal( 'error', $retval );
		}
	}

	public function removeVideo() {

		$articleId = $this->getVal( 'articleId', '' );
		$title = urldecode( $this->getVal( 'title', '' ) );
		$external = $this->getVal( 'external', 0 );
		$rvd = F::build( 'RelatedVideosData' ); /** @var $rvd RelatedVideosData */
		$retval = $rvd->removeVideo( $articleId, $title, $external );
		if ( is_string( $retval ) ) {
			$this->setVal( 'error', $retval );
		}
		else {
			$this->setVal( 'error', null );
		}
	}
}
