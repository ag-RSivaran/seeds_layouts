/**
 * @file
 */

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.test = {
    attach: function (context, settings) {
      // When Click on the checkbox add class to the sibling label selectedStyle
      $(".fieldset-wrapper input[type=radio]").on("click", function () {
        $(this)
          .parent()
          .parent()
          .find("input[type=radio]")
          .each(function () {
            $(this)
              .parent()
              .find("label")
              .removeClass("selectedStyle");
          });
        $(this)
          .parent()
          .find("label")
          .addClass("selectedStyle");
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
