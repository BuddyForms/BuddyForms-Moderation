function BuddyFormsModeration() {
    function buddyforms_moderators_approve() {
        var post_id = jQuery(this).attr('id');
        if (confirm('Approve this Post')) { // todo need il18n
            jQuery.ajax({
                type: 'POST',
                url: buddyformsModeration.ajax,
                data: {
                    "action": "buddyforms_moderators_ajax_approve_post",
                    "post_id": post_id,
                    "nonce": buddyformsModeration.nonce
                },
                success: function (data) {
                    if (isNaN(data)) {
                        alert(data);
                    } else {
                        location.reload();
                    }
                },
                error: function (request) {
                    alert(request.responseText);
                }
            });
        } else {
            return false;
        }
        return false;
    }

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

    function onSubmit([form, event]) {
        BuddyFormsHooks.doAction('bf-moderation:submit:disable');
        var elementStatus = jQuery(form).find('input[name="status"]');
        var clicked = jQuery(form).data('submit-clicked');
        if (elementStatus.length > 0 && clicked) {
            elementStatus.val(clicked);
        }
        BuddyFormsHooks.doAction('bf-moderation:submit:enable');
    }

    function onFormActionClickWrapper(event) {
        var target = jQuery(this).data('target');
        var status = jQuery(this).data('status');
        var targetForms = jQuery('form#buddyforms_form_' + target);
        BuddyFormsHooks.doAction('bf-moderation:submit:click', [targetForms, target, status, event]);
    }

    function onFormActionClick(args) {
        var targetForms = args[0];
        var status = args[2];
        targetForms.data('submit-clicked', status);
    }

    function moderationFieldSlug(fieldData, arguments) {
        if (arguments[0] && arguments[1] && arguments[2] && buddyformsGlobal[arguments[0]] && buddyformsGlobal[arguments[0]]['form_fields'][arguments[1]]) {
            var targetField = buddyformsGlobal[arguments[0]]['form_fields'][arguments[1]];
            if (targetField && targetField.type === 'moderators') {
                return 'buddyforms_moderators';
            }
        }
        return fieldData;
    }

    function moderationFieldValidation(result, arguments) {
        if (arguments[0] && arguments[1] && arguments[2] && buddyformsGlobal[arguments[0]]) {
            var targetField = arguments[2];
            if (targetField && targetField.type === 'moderators') {
                return false;
            }
        }
        return result;
    }

    function addModerationValidation() {
        jQuery.validator.addMethod("has-moderation", function (value, element, param) {
            var formSlug = fncBuddyForms.getFormSlugFromFormElement(element);
            if (
                formSlug && buddyformsGlobal && buddyformsGlobal[formSlug] && buddyformsGlobal[formSlug].js_validation &&
                buddyformsGlobal[formSlug].js_validation[0] === 'disabled'
            ) {
                return true;
            }

            var msjString = 'Please select a Moderator!';
            var isRequired = false;
            var currentFieldSlug = jQuery(element).attr('name');
            if (currentFieldSlug && formSlug) {
                var fieldData = fncBuddyForms.getFieldFrom('moderators', formSlug, 'type');
                if (fieldData.validation_error_message) {
                    msjString = fieldData.validation_error_message;
                }
                isRequired = fieldData && fieldData['required'] && fieldData['required'][0] === 'required';
            }

            if (!isRequired) {
                return true;
            }

            var result = (value && value !== "-1");
            if (!result) {
                jQuery(element).first().parent().find('label.error').remove();
                jQuery(element).first().parent().append("<label id='buddyforms_form_" + currentFieldSlug + "-error' class='error' style='color:red; font-weight: bold; font-style: normal;'>" + msjString + "</label>");
                result = false;
            }

            return result;
        }, "");
    }

    //[fieldSlug, formSlug, fieldData]
    function moderationFieldData(fieldData, arguments) {
        if (arguments[0] && arguments[1] && buddyformsGlobal[arguments[1]]) {
            if (arguments[2]) {
                return arguments[2];
            } else {
                fieldData = fncBuddyForms.getFieldFrom('moderators', arguments[1], 'type');
                return fieldData;
            }
        }
        return fieldData;
    }

    function moderationFieldValidationIgnore(ignore, arguments) {
        if (arguments[0] && arguments[1] && arguments[2] && buddyformsGlobal[arguments[2]]) {
            var targetElement = arguments[0][0];
            var targetElementId = jQuery(targetElement).attr('id');
            if (targetElementId && targetElementId.indexOf('buddyforms_moderators') >= 0) {
                return true;
            }
        }
        return ignore;
    }

    return {
        onFormActionClick: function (e) {
            return onFormActionClick(e);
        },
        init: function () {
            BuddyFormsHooks.addAction('buddyforms:submit', onSubmit, 10);
            BuddyFormsHooks.addAction('bf-moderation:submit:enable', enableModerationFormSubmit);
            BuddyFormsHooks.addAction('bf-moderation:submit:disable', disableModerationFormSubmit);
            jQuery(document.body).on('click', '.buddyforms_moderators_approve', buddyforms_moderators_approve);
            jQuery(document.body).on('click', 'button[type="submit"].bf-moderation', onFormActionClickWrapper);
            BuddyFormsHooks.addAction('bf-moderation:submit:click', onFormActionClick);
            // BuddyFormsHooks.addFilter('buddyforms:field:slug', moderationFieldSlug, 10);
            // BuddyFormsHooks.addFilter('buddyforms:validation:ignore', moderationFieldValidationIgnore, 20);
            // BuddyFormsHooks.addFilter('buddyforms:validation:field:data', moderationFieldData, 20);
            if (jQuery && jQuery.validator && fncBuddyForms) {
                addModerationValidation();
            }
        }
    }
}

var fncBuddyFormsModeration = BuddyFormsModeration();
jQuery(document).ready(function () {
    if (BuddyFormsHooks && buddyformsGlobal) {
        fncBuddyFormsModeration.init();
        BuddyFormsHooks.addAction('buddyforms:init', function () {
            fncBuddyFormsModeration.init();
        }, 10);
    }
});



