/**
 * MPR Reviews JavaScript
 */

(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        MPR_Frontend.init();
    });

    var MPR_Frontend = {
        
        /**
         * Initialize all components
         */
        init: function() {
            this.initStarRating();
            this.initHelpfulButton();
            this.initAjaxSearch();
            this.initFormValidation();
        },

        /**
         * Star rating interaction
         */
        initStarRating: function() {
            var $ratingSelectors = $('.mpr-rating-selector');
            
            if ($ratingSelectors.length === 0) {
                return;
            }

            $ratingSelectors.each(function() {
                var $container = $(this);
                var $options = $container.find('.mpr-rating-option');
                
                $options.on('mouseenter', function() {
                    var rating = $(this).find('input').val();
                    $options.find('.mpr-rating-star').each(function(index) {
                        if (index >= rating) {
                            return false;
                        }
                        $(this).css('color', '#ffc107');
                    });
                });

                $options.on('mouseleave', function() {
                    var $checked = $container.find('input:checked');
                    var checkedRating = $checked.length > 0 ? $checked.val() : 0;
                    
                    $options.find('.mpr-rating-star').each(function(index) {
                        if (index >= checkedRating) {
                            $(this).css('color', '#e4e5e9');
                        } else {
                            $(this).css('color', '#ffc107');
                        }
                    });
                });

                $options.on('click', function() {
                    var rating = $(this).find('input').val();
                    $container.find('.mpr-rating-option input').not($(this).find('input')).prop('checked', false);
                    $(this).find('input').prop('checked', true);
                });
            });
        },

        /**
         * Helpful button click handler
         */
        initHelpfulButton: function() {
            $(document).on('click', '.mpr-helpful-btn', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var reviewId = $button.data('review-id');
                
                if (!reviewId) {
                    return;
                }

                $button.prop('disabled', true).text(MPR_Ajax.loading_text);

                $.ajax({
                    url: mpr_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'mpr_rate_review',
                        nonce: mpr_ajax.nonce,
                        review_id: reviewId
                    },
                    success: function(response) {
                        if (response.success) {
                            $button.text(response.data.count + ' ' + mpr_ajax.helpful_text);
                            $button.addClass('mpr-helpful-voted');
                        } else {
                            alert(response.data.message || mpr_ajax.error_text);
                            $button.prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert(mpr_ajax.error_text);
                        $button.prop('disabled', false);
                        $button.text(MPR_Ajax.helpful_label);
                    }
                });
            });
        },

        /**
         * AJAX search functionality
         */
        initAjaxSearch: function() {
            var $searchForm = $('.mpr-filter-form');
            
            if ($searchForm.length === 0) {
                return;
            }

            // Optional: Use AJAX for filtering instead of page reload
            $searchForm.on('submit', function(e) {
                // Allow normal form submission - AJAX is optional enhancement
            });
        },

        /**
         * Form validation
         */
        initFormValidation: function() {
            var $forms = $('.mpr-review-form');
            
            $forms.each(function() {
                var $form = $(this);
                
                $form.on('submit', function(e) {
                    var isValid = true;
                    var errors = [];

                    // Validate rating
                    var $rating = $form.find('input[name="mpr_review_rating"]:checked');
                    if ($rating.length === 0) {
                        isValid = false;
                        errors.push(mpr_ajax.rating_required || 'Please select a rating');
                    }

                    // Validate title
                    var $title = $form.find('input[name="mpr_review_title"]');
                    if ($title.val().trim().length < 3) {
                        isValid = false;
                        errors.push('Review title must be at least 3 characters');
                    }

                    // Validate content
                    var $content = $form.find('textarea[name="mpr_review_content"]');
                    if ($content.val().trim().length < 10) {
                        isValid = false;
                        errors.push('Review content must be at least 10 characters');
                    }

                    // Validate name
                    var $name = $form.find('input[name="mpr_reviewer_name"]');
                    if ($name.val().trim().length < 2) {
                        isValid = false;
                        errors.push('Please enter your name');
                    }

                    // Validate email
                    var $email = $form.find('input[name="mpr_reviewer_email"]');
                    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test($email.val())) {
                        isValid = false;
                        errors.push('Please enter a valid email address');
                    }

                    if (!isValid) {
                        e.preventDefault();
                        alert(errors.join('\n'));
                        return false;
                    }

                    // Show loading state
                    $form.find('input[type="submit"]').prop('disabled', true).val('Submitting...');
                });
            });
        },

        /**
         * Show notification message
         */
        showNotification: function(message, type) {
            var $notification = $('<div class="mpr-notification mpr-notification-' + type + '">' + message + '</div>');
            
            $notification.appendTo('body');
            
            setTimeout(function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    // Expose to global scope
    window.MPR_Frontend = MPR_Frontend;
    window.MPR_Ajax = {
        loading_text: mpr_ajax.loading_text,
        helpful_text: 'helpful',
        helpful_label: 'Helpful',
        rating_required: 'Please select a rating'
    };

})(jQuery);