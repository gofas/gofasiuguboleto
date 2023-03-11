/**
 * Módulo iugu Pix para WHMCS
 * @copyright	2022 Gofas Software
 * @see			https://gofas.net/?p=14950
 * @license		https://gofas.net/?p=9340
 * @support		https://gofas.net/?p=14690
 * @version		0.1.0
 */
$(document).ready(function () {
    var system_url = $("#system_url").val();
    var invoice_id = $("#invoice_id").val();
    var get_url = "modules/gateways/gofasiugupix/includes/callback.php";
    setInterval(function () {
        $.get(
            system_url + get_url,
            { invoice_id: invoice_id },
            function (data) {
                if (data != "payedPix") {
                   // console.log('status: ' + data);
                }
                if (data == "payedPix") {
                    window.location.reload();
                }
                if (data == "captured") {
                    window.location.reload();
                }
            }
        );
    }, 1000); // Every 1 second
});