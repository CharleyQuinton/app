var WikiaPhotoGallerySlider = {
	sliderEnabled: true,

	init: function(sliderId) {
		var that = this,
			timer = null,
			sliderElem = $('#wikiaPhotoGallery-slider-body-' + sliderId),
			currentImage = $('#wikiaPhotoGallery-slider-' + sliderId + '-0'); // Always start on first image

		//select nav
		currentImage.find('.nav').addClass('selected');

		//show description
		currentImage.find('.description').show();
		currentImage.find('.description-background').show();

		//bind events
		sliderElem.find('.nav').click(function(e) {
			var $mainImage = sliderElem.find('.wikiaPhotoGallery-slider');
			
			if ( $mainImage.queue().length == 0 ){
				clearInterval(timer);
				
				// Open lightbox for videos
				var callback = false;
				if($(e.target).closest('.nav').find('.Wikia-video-play-button').length) {
					callback = function() {
						$mainImage.click();
					}
				}

				WikiaPhotoGallerySlider.scroll($(this), callback);				
			}
		});
		
		$('.wikiaPhotoGallery-slider-body ul li a').click(function() {
			WikiaPhotoGallerySlider.sliderEnabled = false;
		});
		
		// kill slider while closing preview in edit mode
		$(window).bind('EditPagePreviewClosed', function() {
			that.killSlider(timer);
		});

		sliderElem.show();

		//only slideshows with more than one item should animate
		if (sliderElem.find('ul').children().length > 1) {
			timer = setInterval(function() {
				that.slideshow(sliderId);
			}, 7000);
		}
	},

	// kill slider instance - exp. when closing preview in editor
	killSlider: function(timer) {
		clearInterval(timer);
		this.sliderEnabled = true;
	},

	scroll: function(nav, callback) {
		//setup variables
		var thumb_index = nav.parent().index(),
			scroll_by = parseInt(nav.parent().find('.wikiaPhotoGallery-slider').css('left')),
			slider_body = nav.closest('.wikiaPhotoGallery-slider-body'),
			parent_id = slider_body.attr('id'),
			calledBack = false; // don't execute animation callback more than once per scroll

		if (slider_body.find('.wikiaPhotoGallery-slider').queue().length == 0) {

			//set 'selected' class
			slider_body.find('.nav').removeClass('selected');
			nav.addClass('selected');

			//hide description
			slider_body.find('.description').clearQueue().hide();

			//scroll
			slider_body.find('.wikiaPhotoGallery-slider').animate({
				left: '-=' + scroll_by
			}, function() {
				if(calledBack) {
					return;
				}
				slider_body.find( '.wikiaPhotoGallery-slider-' + thumb_index ).find( '.description' ).fadeIn();
				if($.isFunction(callback)) {
					callback();
				}
				calledBack = true;
			});
		}
	},

	slideshow: function(sliderId) {
		if (WikiaPhotoGallerySlider.sliderEnabled) {
			var current = $('#wikiaPhotoGallery-slider-body-' + sliderId + ' .selected').parent().prevAll().length;
			var next = ( ( current == $('#wikiaPhotoGallery-slider-body-' + sliderId + ' .nav').length - 1 ) || ( current > 3 ) ) ? 0 : current + 1;
			WikiaPhotoGallerySlider.scroll($('#wikiaPhotoGallery-slider-' + sliderId + '-' + next).find('.nav'));
		}
	}
}
