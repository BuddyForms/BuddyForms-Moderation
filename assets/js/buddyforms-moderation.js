jQuery(document).ready(function () {
    if (BuddyFormsHooks && buddyformsGlobal) {
        jQuery('form[id^="buddyforms_form_"] button[type="submit"].bf-moderation').click(function () {
            var status = jQuery(this).attr('name');
            jQuery(this).closest('form[id^="buddyforms_form_"]').data('submit-clicked', status);
        });
        BuddyFormsHooks.addAction('buddyforms:submit', function (form) {
            BuddyFormsHooks.doAction('bf-moderation:submit:disable');
            var elementStatus = jQuery(form).find('input[name="status"]');
            var clicked = jQuery(form).data('submit-clicked');
            if (elementStatus.length > 0 && clicked) {
                elementStatus.val(clicked);
            }
            BuddyFormsHooks.doAction('bf-moderation:submit:enable');
        }, 10);

        function disableModerationFormSubmit() {
            var submitButton = jQuery('button.bf-moderation');
            if (submitButton) {
                var target = submitButton.data('target');
                if (target) {
                    submitButton.attr('disabled', 'disabled');
                }
            }
        }

        function enableModerationFormSubmit() {
            var submitButton = jQuery('button.bf-moderation');
            if (submitButton) {
                var target = submitButton.data('target');
                if (target) {
                    submitButton.removeAttr('disabled');
                }
            }
        }

        BuddyFormsHooks.addAction('bf-moderation:submit:enable', enableModerationFormSubmit);
        BuddyFormsHooks.addAction('bf-moderation:submit:disable', disableModerationFormSubmit);
    }
});