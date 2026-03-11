$('#ban_user').on('submit', function(e){
    e.preventDefault();
    let user_id = $('#user_id').val();
    let reasons = $('#reason').val();
    let moders = $('#moder').val();
    $.ajax({
        url: "../core",
        type: "POST",
        data: { action: "ban", userId: user_id, reason: reasons, moder: moders },
        success: function(response) {
            alert(response);
            $("ban_result").textContent = response;
        },
        error: function() {
            alert("Ошибка при блокировке пользователя.");
        }
    });
});

function unBan(id) {
    $.ajax({
        url: "../core",
        type: "POST",
        data: { action: "unban", userId: id },
        success: function(response) {
            alert(response);
            $("ban_result").textContent = response;
        },
        error: function() {
            alert("Ошибка при разблокировке пользователя.");
        }
    });
}

$('#switch_group').on('submit', function(e){
    e.preventDefault();
    let user_id = $('#user_id').val();
    let group = $('#group').val();
    $.ajax({
        url: "../core",
        type: "POST",
        data: { action: "switch_group", userId: user_id, group: group },
        success: function(response) {
            alert(response);
            $("ban_result").textContent = response;
        },
        error: function() {
            alert("Ошибка при смене группы.");
        }
    });
});