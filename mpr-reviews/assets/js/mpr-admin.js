/**
 * MPR Reviews Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initQuickEdit();
        initBulkActions();
    });

    /**
     * Quick Edit functionality
     */
    function initQuickEdit() {
        $('body').on('click', '.editinline', function() {
            var postId = $(this).closest('tr').attr('id');
            postId = postId.replace('post-', '');
            
            var rating = $('input[name="mpr_review_rating"]', '#inline_' + postId).val();
            
            if (rating) {
                setTimeout(function() {
                    $('select[name="mpr_review_rating"]').val(rating);
                }, 100);
            }
        });
    }

    /**
     * Bulk Actions handlers
     */
    function initBulkActions() {
        // Custom bulk actions are registered via WordPress
        // This is for additional AJAX functionality if needed
    }

    /**
     * Update rating in quick edit
     */
    $(document).on('click', '#doaction, #doaction2', function() {
        var $select = $(this).siblings('select');
        var action = $select.val();
        
        if (action === 'mpr_approve' || action === 'mpr_reject') {
            var $form = $('#posts-filter');
            var $hiddenAction = $('<input type="hidden" name="action" value="' + action + '">');
            $form.append($hiddenAction);
        }
    });

})(jQuery);