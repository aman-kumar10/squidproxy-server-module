$(document).ready(function() {
    // Show and Hide Proxy Password
    $('#eyeIcon').click(function() {
        let passwordField = $('#passwordField');
        let isHidden = passwordField.text() === '.................';

        passwordField.html(isHidden ? $('#togglePassword').attr('data-password') : '.................');
        $(this).toggleClass('fa-eye fa-eye-slash');
    });

    // Copy Proxy List
    $('#copyProxyList').click(function() {
        let $btn = $(this);
        let proxyText = $('#proxy_List').text().trim();
    
        let tempInput = $('<textarea>');
        $('body').append(tempInput);
        tempInput.val(proxyText).select();
        document.execCommand('copy');
        tempInput.remove();
    
        $btn.html('<i class="fa fa-check"></i> Copied');
        
        setTimeout(function() {
            $btn.html('<i class="fa fa-copy"></i> Copy');
        }, 3000);
    });

    // Download Proxy List
    $("#downloadProxyList").on("click", function () {
        let proxyListContent = $("#proxy_List").text().trim();

        if (proxyListContent.length > 0) {
            let blob = new Blob([proxyListContent], { type: "text/plain" });
            let link = $("<a>")
                .attr("href", URL.createObjectURL(blob))
                .attr("download", "proxy_list.txt")
                .appendTo("body");

            link[0].click();
            link.remove();

        } else {
            alert("No proxy details available to download.");
        }
    });

});