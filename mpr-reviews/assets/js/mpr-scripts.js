/**
 * MPR Reviews JavaScript
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Star rating hover effect
        $('.mpr-rating-select').on('click', 'input', function() {
            var rating = $(this).val();
            $('.mpr-rating-select .mpr-star').each(function(index) {
                if (index < rating) {
                    $(this).css('color', '#ffc107');
                } else {
                    $(this).css('color', '#ddd');
                }
            });
        });
        
        // Form validation
        $('.mpr-review-form form').on('submit', function(e) {
            var rating = $('select[name="rating"]').val();
            var title = $('input[name="title"]').val();
            var content = $('textarea[name="content"]').val();
            var name = $('input[name="reviewer_name"]').val();
            var email = $('input[name="reviewer_email"]').val();
            
            if (!rating || !title || !content || !name || !email) {
                alert('Please fill in all required fields.');
                e.preventDefault();
                return false;
            }
            
            if (title.length < 3) {
                alert('Review title must be at least 3 characters.');
                e.preventDefault();
                return false;
            }
            
            if (content.length < 10) {
                alert('Review content must be at least 10 characters.');
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    });
    
})(jQuery);