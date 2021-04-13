const jQuery = window && window.jQuery;

jQuery(document).ready(() => {
    jQuery(document).on(
        "click",
        ".rrze-newsletter-activation-notice .notice-dismiss",
        () => {
            const data = {
                action: "rrze_newsletter_activation_notice_dismiss",
            };
            const { ajaxurl } =
                window &&
                window.rrze_newsletter_activation_notice_dismiss_params;
            jQuery.post(ajaxurl, data, () => null);
        }
    );
});
