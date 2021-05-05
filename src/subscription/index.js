/**
 * Plugin dependencies
 */
import "./style.scss";
import $ from "jquery";

$(document).ready(() => {
    if ($("#unsubscribe-all").prop("checked")) {
        $(".newsl-subsc-checkbox").prop("disabled", true);
        $(".newsl-subsc-list").toggleClass("newsl-subsc-list--disabled");
    }
    $(document).on("change", "#unsubscribe-all", () => {
        $(".newsl-subsc-checkbox").prop("disabled", (i, v) => !v);
        if ($(".newsl-subsc-list").hasClass("newsl-subsc-list--disabled")) {
            $(".newsl-subsc-checkbox").prop("checked", true);
            $(".newsl-subsc-list").removeClass("newsl-subsc-list--disabled");
        } else {
            $(".newsl-subsc-checkbox").prop("checked", false);
            $(".newsl-subsc-list").addClass("newsl-subsc-list--disabled");
        }
    });
});
