<div class="WikiaLightbox">
	<header class="LightboxHeader hidden">
	</header>

	<div class="lightbox-arrows">
		<span id="LightboxNext" class="arrow next"></span>
		<span id="LightboxPrevious" class="arrow previous"></span>
	</div>
	
	<div class="media">
	</div>
	
	<div id="LightboxCarousel" class="LightboxCarousel hidden">
	</div>
	
	<div class="more-info">
	</div>
	
	<div class="share">
	</div>

	<script id="LightboxPhotoTemplate" class="template" type="text/template">
		<img src="{{imageUrl}}" height="{{imageHeight}}" >
	</script>
	
	<script id="LightboxVideoTemplate" class="template" type="text/template">
		{{{videoEmbedCode}}}
	</script>
	
	<script id="LightboxHeaderTemplate" class="template" type="text/template">
		<button class="more-info-button secondary"><?= wfMsg('lightbox-header-more-info-button') ?></button>
		<button class="share-button secondary"><?= wfMsg('lightbox-header-share-button') ?></button>
		<h1><a href="{{fileUrl}}" target="_blank">{{fileTitle}}</a></h1>
		<a href="{{rawImageUrl}}" class="see-full-size-link" target="_blank"><?= wfMsg('lightbox-header-see-full-size-image') ?></a>
		<div class="user-details">
			<img class="avatar" src="{{userThumbUrl}}">
			<?= wfMsg('lightbox-header-added-by', '<a href="{{userPageUrl}}" target="_blank">{{userName}}</a>') ?>
			<span class="posted-in">
				<?= wfMsg('lightbox-header-posted-in', '{{#smallerArticleList}}<span class="posted-in-article"><a href="{{articleUrl}}" target="_blank">{{articleTitle}}</a></span>{{/smallerArticleList}}{{#articleListIsSmaller}}&hellip;{{/articleListIsSmaller}}') ?>
			</span>
		</div>
	</script>

	<script type="text/javascript">
		var initialFileDetail = <?= json_encode($initialFileDetail) ?>;
		var mediaThumbs = <?= json_encode($mediaThumbs) ?>;
	</script>
	
	<script id="LightboxCarouselTemplate" type="text/template">
		<div class="ad"></div>
		<div class="content">
			<ul class="toolbar">
				<li><span class="icon pin button secondary"></span></li>
			</ul>
			<p id="LightboxCarouselProgress" class="progress"></p>
			<span class="carousel-arrow next"></span>
			<span class="carousel-arrow previous"></span>
 			<div id="LightboxCarouselContainer" class="LightboxCarouselContainer">
 				<div>
 					<ul class="carousel">
 						{{#thumbs}}
 							<li>
 								{{{playButtonSpan}}}
 								<img src="<?= $wg->BlankImgUrl ?>" data-src="{{thumbUrl}}">
 							</li>
 						{{/thumbs}}
 					</ul>
 				</div>
 			</div>
		</div>
	</script>
	
	<script id="LightboxCarouselProgressTemplate" type="text/template">
		<?= wfMsg('lightbox-carousel-progress', array("{{idx1}}", "{{idx2}}", "{{total}}")); ?>
	</script>
	
	<script id="LightboxMoreInfoTemplate" type="text/template">
		<button class="more-info-close secondary"><?= wfMsg('lightbox-more-info-back-button') ?></button>
		<div class="content">
			<div class="hero">
				<img src="{{imageUrl}}">
			</div>
			<div class="more-info-details">
				<h1><a href="{{fileUrl}}" target="_blank">{{fileTitle}}</a></h1>
				<div class="user-details">
					<img class="avatar" src="{{userThumbUrl}}">
					<?= wfMsg('lightbox-header-added-by', '<a href="{{userPageUrl}}" target="_blank">{{userName}}</a>') ?>
					<span class="posted-in">
						<?= wfMsg('lightbox-header-posted-in', '{{#articles}}<span class="posted-in-article"><a href="{{articleUrl}}" target="_blank">{{articleTitle}}</a></span>{{/articles}}') ?>
					</span>
				</div>
				{{#description}}
					<h2><?= wfMsg('lightbox-more-info-description-heading') ?></h2>
					<p class="more-info-description">{{description}}</p>
				{{/description}}
				<h2><?= wfMsg('lightbox-more-info-filelinks-heading') ?></h2>
				<ul>
				{{#articles}}
					<li><a href="{{articleUrl}}" target="_blank">{{articleTitle}}</a></li>
				{{/articles}}
				</ul>
			</div>
		</div>
	</script>

	<script id="LightboxShareTemplate" type="text/template">
		<button class="more-info-close secondary"><?= wfMsg('lightbox-more-info-back-button') ?></button>
		<div class="content">
			<div class="hero">
				<img src="{{imageUrl}}">
			</div>
			<div class="share-form">
				<h1><a href="{{fileUrl}}" target="_blank">{{fileTitle}}</a></h1>
				<?php 
					$form = array (
					    'inputs' => array (
					        array(
					            // Main input attributes
					            'label' => wfMsg('lightbox-standard-link'),
					            'type' => 'text',
					            'name' => 'lightbox-standard-link',
					            // extra attributes
					            'attributes' => array(
					                'value' => "{{standardLink}}",
					            )
					        )
					    )
					);
				?>
				<?= wfRenderModule('WikiaForm', 'Index', array('form' => $form)); ?>
				<div class="social-links">
					Social network links go here…
				</div>
			</div>
			<div class="bottom-forms">
				<div class="more-links">
					<?php 
						$form = array (
						    'inputs' => array (
						        array(
						            // Main input attributes
						            'label' => wfMsg('lightbox-embed-url'),
						            'type' => 'text',
						            'name' => 'lightbox-embed-url',
						            // extra attributes
						            'attributes' => array(
						                'value' => "{{embedUrl}}",
						            )
						        ),
						        array(
						            // Main input attributes
						            'label' => wfMsg('lightbox-file-page-url'),
						            'type' => 'text',
						            'name' => 'lightbox-file-page-url',
						            // extra attributes
						            'attributes' => array(
						                'value' => "{{filePageUrl}}",
						            )
						        )
						    )
						);
					?>
					<?= wfRenderModule('WikiaForm', 'Index', array('form' => $form)); ?>								
				</div>
				<div class="email">
					<?php 
						$form = array (
						    'inputs' => array (
						        array(
						            // Main input attributes
						            'label' => wfMsg('lightbox-email-label'),
						            'type' => 'text',
						            'name' => 'lightbox-email',
						            // extra attributes
						            'attributes' => array(
						                'placeholder' => wfMsg('lightbox-email-placeholder'),
						            )
						        )
						    ),
							'submits' => array(
								array(
							    	'value' => wfMsg('lightbox-email-submit')
								)
							)
						);
					?>
					<?= wfRenderModule('WikiaForm', 'Index', array('form' => $form)); ?>				
				</div>
			</div>
		</div>
	</script>


</div>