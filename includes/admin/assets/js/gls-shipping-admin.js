(function () {
    jQuery(document).ready(function ($) {
        $(".gls-print-label").on("click", function () {
            $(this).prop("disabled", true);
            $.ajax({
                url: gls_croatia.adminAjaxUrl,
                method: "POST",
                data: {
                    postNonce: gls_croatia.ajaxNonce,
                    action: "gls_generate_label",
                    orderId: $(this).attr("order-id"),
                },
            }).done(function (response) {
                $(".gls-print-label").prop("disabled", false);
                if (response.data && response.data.success) {
                    window.location.reload();
                } else {
                    $("#gls-info").html(
                        `<span style="color:red;">${
                            response.data && response.data.error
                        }</span>`
                    );
                }
            });
        });
    });
})(jQuery);
