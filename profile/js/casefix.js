function case_count(userId) {
    $.ajax({
        url: './case_core',
        type: 'POST',
        data: {userId: userId, action: "get_count"},
        success: function(response) {
            $('.case_count').html(response);
            
        },
        error: function(xhr, status, error) {
            alert('Произошла ошибка при вывода количества кейсов.');
            console.log(error);
        }
    });
}
function openCase(userId) {
    $.ajax({
    url: './case_core',
    type: 'POST',
    data: {userId: userId, action: "open"},
    success: function(response) {
        $('#case_result').html(response);
        if (response !== "У вас нет кейсов." && response !== "Всмысле вы бомж по кейсам?...") {
            console.log(response);
            $('#case_history').show();
            $('#history').prepend(`<p>${response}</p>`);
            case_count(userId);
        }
    },
    error: function(xhr, status, error) {
        $('#case_result').html('Произошла ошибка при открытии кейса.');
        console.log(error);
    }
    });
}