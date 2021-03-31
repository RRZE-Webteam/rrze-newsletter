/******/ (function() { // webpackBootstrap
var __webpack_exports__ = {};
/*!****************************!*\
  !*** ./src/admin/index.js ***!
  \****************************/
var jQuery = window && window.jQuery;
jQuery(document).ready(function () {
  jQuery(document).on("click", ".rrze-newsletter-activation-notice .notice-dismiss", function () {
    var data = {
      action: "rrze_newsletter_activation_notice_dismiss"
    };

    var _ref = window && window.rrze_newsletter_activation_nag_dismissal_params,
        ajaxurl = _ref.ajaxurl;

    jQuery.post(ajaxurl, data, function () {
      return null;
    });
  });
});
/******/ })()
;
//# sourceMappingURL=admin.js.map