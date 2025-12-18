function getLoterey(userId) {
    $.ajax({
    url: './loterey_core',
    type: 'POST',
    data: {userId: userId},
    success: function(response) {
        $('#result').html(response);
    },
    error: function(xhr, status, error) {
        $('#case_result').html('Произошла ошибка при открытии кейса.');
        console.log(error);
    }
    });
}