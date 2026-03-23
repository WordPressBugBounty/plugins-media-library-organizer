jQuery(document).ready(function ($) {
    if (!$('body').hasClass('upload-php')) {
        return;
    }

    /**
     * Helper function to add Replace Media button to attachment actions
     * @param {Object} view - The attachment view instance
     */
    function addReplaceMediaButton(view) {
        const $el = view.$el;
        const $actions = $el.find('.actions');

        if (!$actions.length || $actions.find('.mlo-replace-media-link').length) {
            return;
        }

        const attachmentId = view.model.get('id');

        if (!attachmentId) {
            return;
        }

        const editUrl = MLOModalAttachment.editPostURL + '?post=' + attachmentId +
            '&action=edit&TB_iframe=true&width=90%&height=90%';

        const $editLink = $actions.find('a[href*="post.php"]');
        if ($editLink.length) {
            $editLink.after(
                ' <span class="links-separator">|</span>' +
                '<a href="' + editUrl + '" class="mlo-replace-media-link thickbox" title="' +
                MLOModalAttachment.i18n.replaceMedia + '"> ' +
                MLOModalAttachment.i18n.replaceMedia + '</a>'
            );
        }
    }

    /**
     * Extend a WordPress media view with Replace Media functionality
     * @param {Object} OriginalView - The original view to extend
     * @returns {Object} Extended view
     */
    function extendMediaView(OriginalView) {
        return OriginalView.extend({
            initialize: function () {
                OriginalView.prototype.initialize.apply(this, arguments);
            },

            render: function () {
                OriginalView.prototype.render.apply(this, arguments);
                addReplaceMediaButton(this);
                return this;
            }
        });
    }

    const originalAttachmentDetails = wp.media.view.Attachment.Details;
    wp.media.view.Attachment.Details = extendMediaView(originalAttachmentDetails);

    if (wp.media.view.Attachment.Details.TwoColumn) {
        const originalTwoColumn = wp.media.view.Attachment.Details.TwoColumn;
        wp.media.view.Attachment.Details.TwoColumn = extendMediaView(originalTwoColumn);
    }

    $(document).on('click', '.mlo-replace-media-link.thickbox', function () {
        tb_init('a.thickbox');
    });
});
