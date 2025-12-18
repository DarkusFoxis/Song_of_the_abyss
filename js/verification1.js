$('#email_form').on('submit', function(e){
    e.preventDefault();
    let code = $('#email').val();
    $.ajax({
        url: "../core",
        type: "POST",
        data: { action: "verification", code: code },
        success: function(response) {
            alert(response);
        },
        error: function() {
            alert("Ошибка при проверке кода.");
        }
    });
});

$('#resend_code').on('click', function(e){
    $.ajax({
        url: "../core",
        type: "POST",
        data: { action: "resend_code"},
        success: function(response) {
            alert(response);
        },
        error: function() {
            alert("Ошибка при проверке кода.");
        }
    });
});