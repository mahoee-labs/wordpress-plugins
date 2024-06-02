(function ($) {
  $(document).ready(function () {
    $(".mahoee-scheduling-block .option").click(function () {
      $(".mahoee-scheduling-block .option").attr("data-selected", "false");
      $(this).attr("data-selected", "true");
      $(".mahoee-scheduling-block").attr("data-state", "confirming");
      $(".mahoee-scheduling-block .confirm").prop("disabled", false);
      $(".mahoee-scheduling-block .change").prop("disabled", false);
    });

    $(".mahoee-scheduling-block .change").click(function () {
      $(".mahoee-scheduling-block .option").attr("data-selected", "false");
      $(".mahoee-scheduling-block").attr("data-state", "initial");
      $(".mahoee-scheduling-block .confirm").prop("disabled", true);
      $(".mahoee-scheduling-block .change").prop("disabled", true);
    });
  });
})(jQuery);
