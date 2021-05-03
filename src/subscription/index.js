/**
 * Plugin dependencies
 */
import "./style.scss";

const jQuery = window && window.jQuery;

jQuery(document).ready(() => {
    if (jQuery("#unsubscribe-all").prop("checked")) {
        jQuery(".newsl-subsc-checkbox").prop("disabled", true);
        jQuery(".newsl-subsc-list").toggleClass("newsl-subsc-list--disabled");
    }
    jQuery("#unsubscribe-all").change(function () {
        jQuery(".newsl-subsc-checkbox").prop("disabled", (i, v) => !v);
        if (
            jQuery(".newsl-subsc-list").hasClass("newsl-subsc-list--disabled")
        ) {
            jQuery(".newsl-subsc-checkbox").prop("checked", true);
            jQuery(".newsl-subsc-list").removeClass(
                "newsl-subsc-list--disabled"
            );
        } else {
            jQuery(".newsl-subsc-checkbox").prop("checked", false);
            jQuery(".newsl-subsc-list").addClass("newsl-subsc-list--disabled");
        }
    });
});
