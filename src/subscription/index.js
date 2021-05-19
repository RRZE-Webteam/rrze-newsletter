/**
 * Plugin dependencies
 */
import "./style.scss";
import $ from "jquery";

$(document).ready(() => {
    if ($(".rrze-newsletter-subscription #unsubscribe-all").prop("checked")) {
        $(".rrze-newsletter-subscription .checkbox").prop("disabled", true);
        $(".rrze-newsletter-subscription .list").toggleClass("list--disabled");
    }
    $(document).on(
        "change",
        ".rrze-newsletter-subscription #unsubscribe-all",
        () => {
            $(".rrze-newsletter-subscription .checkbox").prop(
                "disabled",
                (i, v) => !v
            );
            if (
                $(".rrze-newsletter-subscription .list").hasClass(
                    "list--disabled"
                )
            ) {
                $(".rrze-newsletter-subscription .checkbox").prop(
                    "checked",
                    true
                );
                $(".rrze-newsletter-subscription .list").removeClass(
                    "list--disabled"
                );
            } else {
                $(".rrze-newsletter-subscription .checkbox").prop(
                    "checked",
                    false
                );
                $(".rrze-newsletter-subscription .list").addClass(
                    "list--disabled"
                );
            }
        }
    );
});
