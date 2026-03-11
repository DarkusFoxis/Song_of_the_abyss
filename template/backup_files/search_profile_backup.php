<?php
    session_start();
    if(!isset($_SESSION['user'])) {
        header("Location: login");
        exit();
    }
    require_once '../template/conn.php';
    
    $conn = mysqli_connect($host, $log, $password_sql, $database);
    if(!$conn){
        $_SESSION['form_error'] = "Ошибка соединения. Пожалуйста, сообщите ошибку создателю: " . mysqli_connect_error();
    } else {
        $login = $_SESSION['user'];
        $user_query = "SELECT u.*, sg.lvl FROM users u JOIN site_group sg ON u.permissions = sg.name WHERE u.login = '$login'";
        $result = $conn->query($user_query);
    
        if($result -> num_rows > 0){
            $user = $result -> fetch_assoc();
            $permissions = $user["permissions"];
            $lvl = $user['lvl'];
                    
            if($lvl == 0) {
                $_SESSION["perm_error"] = "Причина отказа: Вы были заблокированны на сайте. Ваши права: " . $permissions . ".<br> Если вы считаете, что вы были заблокированны по ошибке, обратитесь к создателю."; 
                header("Location: ../403");
            } else if ($lvl == 1) {
                $_SESSION['perm_error'] = "Причина отказа: Вы не подтверждены на сайте. Ваши права: " . $permissions . ".<br> Обратитесь к создателю, для подтверждения аккаунта.";
                header("Location: ../403");
            }
        }
    }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Просмотр профилей</title>
    <link rel = "icon" href = "../img/icon.png">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<link rel = "stylesheet" href = "../style/style.css">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@200;300&amp;display=swap" rel="stylesheet">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style>
	    p{
	        margin-bottom: 6px;
	    }
        .profile-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }
        .input-text{
			width: 300px;
		}
        .profile {
            background: linear-gradient(135deg, #10001e, #200030);
            border-radius: 8px;
            width: 300px;
            height: 105px;
            margin: 5px;
            box-sizing: border-box;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease-in-out;
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }
        .profile::before{
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, transparent 50%, rgba(100, 0, 190, 0.1) 80%, rgba(74, 0, 122, 0.2) 100%);
            pointer-events: none;
        }

        .profile:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.5);
        }
        
        .profile_modal{
            width: 250px;
            height: 135px;
            margin-bottom: 20px;
            box-sizing: border-box;
        }
        .button {
           padding: 5px 7px;
           background-color: #6a0080;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }
        .button:hover {
            background-color: #7f00a4;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.7);
        }
       .modal-content {
            background: linear-gradient(135deg, #1a002b, #30004b);
            margin: 15% auto;
            margin-top: 0px;
            padding: 20px;
            border: 2px solid #4a007a;
            width: 85%;
            border-radius: 8px;
            color: white;
            position: relative;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
        }
        .modal-content .avatar{
            width: 150px;
            height: 150px;
            margin-right: 10px;
        }
        .modal-content::before{
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, transparent 50%, rgba(100, 0, 190, 0.1) 80%, rgba(74, 0, 122, 0.2) 100%);
            pointer-events: none;
            border-radius: 8px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover, .close:focus {
            color: white;
            text-decoration: none;
            cursor: pointer;
        }
        .donate {
		    border-radius: 20px;
		    padding: 5px;
		    background: radial-gradient(circle,rgba(238, 174, 202, 0.1) 0%, rgba(148, 187, 233, 0.4) 100%);
		}
    </style>
</head>
<body>
<div class="navbar">
	<a href="./main">Back</a>
	<a href='./table_of_leader'>Table of leaders</a>
</div>
<div class="content-main">
	<div class="container">
		<div class="row">
			<div class="col-12">
				<div class="header">
				    <h3>Просмотр профиля</h3>
				</div>
				<p>Ниже представленны профили, которые сейчас есть в системе.</p>
                <div id="profileModal" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <div id="profile-content"></div>
                    </div>
                </div>
				<div class="profile-container">
					<?php
                        $sql = "SELECT * FROM users";
                        $result = mysqli_query($conn, $sql);

                        while ($row = mysqli_fetch_assoc($result)) {
                            if ($row['donate'] < 100) {
                                $border_color = '#33004b';
                            } else if ($row['donate'] < 1000) {
                                $border_color = '#005599';
                            } else if ($row['donate'] < 2000) {
                                $border_color = '#0066cc';
                            } else if ($row['donate'] < 5000) {
                                $border_color = '#ff8c00';
                            } else {
                                $border_color = '#FFD700';
                            }
                            echo '<div class="profile" style="border: 2px solid' . $border_color . '">';
                            echo '<img src="./avatars/' . $row["avatar"] . '" class="avatar" align="left" style="width: 100px; height:100px">';
                            $title_sql = "SELECT title FROM title WHERE id_title = (SELECT id_title FROM invent WHERE id_user = {$row['id']})";
                            $title_result = mysqli_query($conn, $title_sql);
                            
                            if (mysqli_num_rows($title_result) > 0) {
                                $title_row = mysqli_fetch_assoc($title_result);
                                $title = $title_row['title'];
                                echo '<p>' . $title . ': ' . $row["username"] . '</p>';
                            } else {
                                echo '<p>Никнейм: ' . $row["username"] . '</p>';
                            }
                            echo '<p><button class="button" onclick="idSend(\'' . $row["id"] . '\')">Перейти в профиль</button></p>';
                            echo '</div><br>';
                        }
                        mysqli_close($conn);
                    ?>
                </div>
		    </div>
	    </div>
    </div>
</div>
<script src="../js/jquery-3.7.1.min.js"></script>
<script>
    function idSend() {}
    $(document).ready(function() {
        $(".button").click(function() {
            var userId = $(this).attr("onclick").match(/idSend\('(.*?)'\)/)[1];
            loadProfileModal(userId);
        });

        function loadProfileModal(userId) {
            $.ajax({
                url: "load_profile",
                type: "POST",
                data: { userId: userId },
                success: function(response) {
                    $("#profile-content").html(response);
                    $("#profileModal").show();
                    $(window).scrollTop(0);
                },
                error: function() {
                    alert("Ошибка при загрузке профиля.");
                }
            });
        }
        $(".close").click(function() {
            $("#profileModal").hide();
        });
        $(window).click(function(event) {
            if ($(event.target).is("#profileModal")) {
                $("#profileModal").hide();
            }
        });
    });
</script>
<datalist id="ban_reason">
    <option>Злоупотребление просмотра кода</option>
    <option>Подозрение во взломе аккаунта</option>
    <option>Нарушение правил сайта</option>
</datalist>
<datalist id="group_list">
    <option>ROOR</option>
    <option>BETA</option>
    <option>USER</option>
    <option>GUEST</option>
    <option>WRITER</option>
</datalist>
</body>
</html>
