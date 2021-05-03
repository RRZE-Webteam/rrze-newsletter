/**
 * Plugin dependencies
 */
import $ from "jquery";

$(document).ready(() => {
    $(document).on(
        "click",
        ".rrze-newsletter-activation-notice .notice-dismiss",
        () => {
            const data = {
                action: "rrze_newsletter_activation_notice_dismiss"
            };
            const { ajaxurl } =
                window &&
                window.rrze_newsletter_activation_notice_dismiss_params;
            $.post(ajaxurl, data, () => null);
        }
    );
});
