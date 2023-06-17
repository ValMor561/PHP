<?php
session_start();
$messageToEdit = -1;
$messageOnPage = 3;
$pagesAround = 2;

//Авторизация
if (isset($_POST["login"]) && isset($_POST["password"])) {
    $fileName = "users.txt";
    touch($fileName);
    $stream = fopen($fileName, "r+b");
    flock($stream, LOCK_SH);
    $str = fread($stream, filesize("users.txt"));
    $usersArr = unserialize($str);    
    $login = rtrim(htmlspecialchars($_POST["login"]), " ");
    $password = rtrim(htmlspecialchars($_POST["password"]), " ");
    
    //проверяю пароль
    if (key_exists($login, $usersArr) && md5($password) === $usersArr[$login][0]) {
        $_SESSION['user'] = $login;
        $_SESSION['permission'] = $usersArr[$login][1];
    }
    
    //перенаправляю скрипт
    fclose($stream);
    header("Location: http://".$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME']);
    die();
}

//выход
if (isset($_POST["exit"])) {
    session_destroy();
    $_SESSION = [];
    header("Location: http://".$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME']);
    die();
}

//отправка сообщения
if (isset($_POST['message'])) {
    $messageArr = array();
    $messageArr["name"] = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : $_SESSION['user'];
    $messageArr["message"] = rtrim(htmlspecialchars($_POST['message']), "\n\r");
    
    //записываю сообщение
    if ($messageArr["message"] != "") {   
        $messageArr["address"] = $_SERVER['REMOTE_ADDR'];
        $messageArr["time"] = date('Y-m-d H:i:s');
        $messageArr["edit"] = false;
        touch("guestbook.txt");
        $stream = fopen("guestbook.txt", "a+b");
        flock($stream, LOCK_EX);
        fwrite($stream, serialize($messageArr));
        fclose($stream);
        header("Location: http://".$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME']);
    }
}

//удаления сообщения
if (isset($_POST['delete'])) {
    $stream = fopen("guestbook.txt", "a+b");
    flock($stream, LOCK_EX);
    $str = fread($stream, filesize("guestbook.txt"));
    $messages = arrayMessage($str);
    $message = $messages[$_POST['delete']];
    $strToReplace = serialize($message);
    $str = str_replace($strToReplace, "", $str);
    
    //перезаписываю файл
    ftruncate($stream, 0);
    fwrite($stream, $str);
    fclose($stream);
    header("Location: http://".$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME']);
}

//текущая страница
$pageNow = 1;
if (isset($_POST["page"])) {
    $pageNow = $_POST["page"];
    $_SESSION["page"] = $pageNow;
}

//сохранение страницы 
if (isset($_SESSION["page"])) {
    $pageNow = $_SESSION["page"];
}

//сообщение которое надо изменить
if (isset($_POST['edit'])) {
    $messageToEdit = $_POST['edit'];
}

//изменение сообщения
if (isset($_POST['editSubmit'])) {
    $stream = fopen("guestbook.txt", "a+b");
    flock($stream, LOCK_EX);
    $str = fread($stream, filesize("guestbook.txt"));
    $messages = arrayMessage($str); 
    $message = $messages[$_POST['editSubmit']];
    $strToReplace = serialize($message);
    
    //замена содержимого сообщения
    if ($message["message"] != $_POST["editMessage"]) {
        $message["message"] = $_POST["editMessage"];
        $message["editTime"] = date('Y-m-d H:i:s');
        $message["editName"] = $_SESSION["user"];
        $message["edit"] = true;
    }
    
    //запись в файл
    $strOnReplace = serialize($message);
    $str = str_replace($strToReplace, $strOnReplace, $str);
    ftruncate($stream, 0);
    fwrite($stream, $str);
    fclose($stream);
    header("Location: http://".$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME']);
}

//Считываю из файла
touch("guestbook.txt");
$str = file_get_contents("guestbook.txt");
$messages = arrayMessage($str);

//вывожу html
$html = getHeader();
$html .= (isAuthorised()) ? getLogoutForm(isAuthorised()).getSendForm(isAuthorised()) : getLoginForm().getSendForm(isAuthorised());
if (count($messages) > 0) {
    $html .= getPage($messages, $messageOnPage, $pageNow, $pagesAround, $messageToEdit);
}

//подвал
$endOfHtml = '</body> </html>';
print ($html.$endOfHtml);

/**
 * Возвращает форму страницы
 * 
 * @param array   $__messages      Массив сообщений.
 * @param integer $__messageOnPage Количество сообщений на странице.
 * @param integer $__selectedPage  Выбранная страница.
 * @param integer $__pagesAround   Количество ссылок вокруг текущей.
 * @param integer $__messageToEdit Сообщение необходимое изменить.
 * 
 * @return string Форма
 */
function getPage(array $__messages, int $__messageOnPage, int $__selectedPage, int $__pagesAround, int $__messageToEdit)
{
    $html = '<div class="inner" id="last_block">
            <div class="message_page">';
    $html .= getMessages($__messages, $__messageOnPage, $__selectedPage, $__messageToEdit);    
    $html .= '<form method="POST" class="page_list">
                    <ul>';
    $countPages = ceil(count($__messages) / $__messageOnPage);
    $start = $__selectedPage - $__pagesAround < 0 ? 0 : $__selectedPage - $__pagesAround - 1;
    $end = $__selectedPage + $__pagesAround >= $countPages ? $countPages : $__selectedPage + $__pagesAround;
   
    //кнопка первой страницы
    if ($start + 1 >= 2) {
        $html .= '<li><button name="page" value="1">1</button></li>';
    }
    
    //добавляю в начало ...
    if ($start + 1 > 2) {
        $html .= '<li class="pointer">...</li>';
    }
    
    //кнопки вокруг
    for ($index = $start + 1; $index <= $end; ++$index) {  
        if ($index == 0) {
            continue;
        } 
        
        //начало кнопок
        $html .= '<li><button ';
        
        //выбранная страница
        if ($index == $__selectedPage) {
            $html .= 'id="selected"';
        }
        
        //просто страницы
        $html .= 'name="page" value="'.$index.'">'.$index.'</button></li>';
    }
    
    //добавляю в конец ...
    if ($end + 1 < $countPages) {
        $html .= '<li class="pointer">...</li>';
    }
    
    //кнопка последней страницы
    if ($end < $countPages) {
        $html .= '<li><button '; 
        if ($__selectedPage == $countPages) {
            $html .= 'id="selected"';
        }
        
        //конец кнопок
        $html .= 'name="page" value="'.$countPages.'">'.$countPages.'</button></li>';
    }
    
    //конец формы
    $html .= '</ul> </form> </div> </div>';  
    return $html;
}

/**
 * Получаю форму сообщения
 * 
 * @param array   $__messages      Массив с собщениями.
 * @param integer $__messageOnPage Количество сообщений на странице.
 * @param integer $__selectedPage  Выбранная страница.
 * @param integer $__messageToEdit Сообщение необходимое изменить.
 * 
 * @return string Форма
 */
function getMessages(array $__messages, int $__messageOnPage, int $__selectedPage, int $__messageToEdit)
{
    $html = "";
    $startMessage = ($__selectedPage - 1) * $__messageOnPage;
    $endMessage = $startMessage + $__messageOnPage < count($__messages) ? $startMessage + $__messageOnPage : count($__messages);
    for ($index = $startMessage; $index < $endMessage; ++$index) {
        $html .= '<div class="message">
                    <form method="POST">
                    <p class="name">'.$__messages[$index]["name"];
        
        //если нажата ктопка редактировать
        if ($__messageToEdit == $index) {
            $html .= '<p><input autofocus autocomlete="off" type="text" name="editMessage" value="'.$__messages[$index]["message"].'">';
            $html .= '</input></p><p class="time">'.$__messages[$index]["time"]." ";
            if (isset($_SESSION['user']) && ($_SESSION['user'] == "admin")) {
                $html .= '<i>'.$__messages[$index]["address"].'</i>';
            };
            $html .= '<button type="submit" name="editSubmit" value="'.$index.'">Редактировать</button> </p> </form> </div>';
            continue;
        }
        
        //если админ или пользватель который написал сообщение добавляю кнопку удалить и редактировать
        if (isset($_SESSION['user']) && ($_SESSION['user'] == $__messages[$index]["name"] || $_SESSION['permission'] == "admin")) {
            $html .= '<button type-"submit" name="edit" value="'.$index.'"> <svg xmlns="http://www.w3.org/2000/svg" width="16" 
                height="16" fill="green" class="bi bi-pencil" viewBox="0 0 16 16">
                <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 
                 .11-.168l10-10zM11.207 2.5L13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 
                .5.5v.5h.293l6.5-6.5zm-9.761 5.175l-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 
                0 1-.468-.325z"/> </svg>';
            $html .= '<button type="submit" name="delete" value="'.$index.'"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" 
                fill="red" class="bi_bi-trash" viewBox="0 0 16 16">
                <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 
                0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 
                1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4L4 4.059V13a1 1 0 0 0 1 
                1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                </svg> </button>';
        }   
        
        //Добавляю сообщение и время
        $html .= '</p class="text">
                    <p>'.$__messages[$index]["message"].'</p>
                    <p class="time">';
        $html .= $__messages[$index]["edit"] 
            ? '<i>Изменено пользователем '.$__messages[$index]["editName"].'</i> '.$__messages[$index]["editTime"]." " 
            : $__messages[$index]["time"]." ";
        if (isset($_SESSION['user']) && ($_SESSION['permission'] == "admin")) {
            $html .= '<i>'.$__messages[$index]["address"].'</i>';
        }
        
        //конец формы
        $html .= '</p> </form> </div>';
    }
    
    //возвращаю форму
    return $html; 
}

/**
 * Собирает из строки массив сообщений
 * 
 * @param string $__str Строка.
 * 
 * @return Массив сообщений
 */
function arrayMessage(string $__str)
{
    $newMessageArr = array();
    $messages = explode(";}", $__str);
    array_pop($messages);
    foreach ($messages as $lines => $message) {
        $message .= ";}";
        $newMessageArr[$lines] = unserialize($message);
    }
    
    //Разворачиваю массив чтобы сообщения были в обратном порядке
    $newMessageArr = array_reverse($newMessageArr);
    return ($newMessageArr);
}

/**
 * Проверяет авторизован ли пользователь
 * 
 * @return string/bool Имя пользователя если да false если нет
 */
function isAuthorised()
{
    return isset($_SESSION['user']) ? $_SESSION['user'] : false;
}

/**
 * Возвращает начало html страницы
 * 
 * @return string 
 */
function getHeader()
{
    return '<!DOCTYPE html>
            <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <title>Guest book</title>
                    <link rel="stylesheet" href="style.css">
                </head>
                <body>';
}

/**
 * Форма авторизации 
 * 
 * @return string Форма
 */
function getLoginForm()
{
    return '<div class="inner">
                <div class="login">
                    <h2>Вход</h2>              
                    <form method="POST">
                        <input type="text" name="login" placeholder="Логин" required>
                        <input type="password" name="password" placeholder="Пароль" required>
                        <button name="button" type="submit"> Авторизация </button>
                    </form>
                </div>
            </div>';
}

/**
 * Возвращает форму с выходом для авторизованных пользователей
 * 
 * @param string $__login Имя пользователя.
 * 
 * @return string Форма
 */
function getLogoutForm(string $__login)
{
    return '<div class="inner">
            <div class="authorise_user">
            <form method="POST">
                <p>Добро пожаловать:<output name="user"> '.$__login.'</output></p>
                <button type="submit" name="exit" >Выйти</button>
            </form>
            </div>
        </div>';
}

/**
 * Возвращает форму отправки сообщения
 * 
 * @param string $__isAuthorised Авторизован ли пользователь.
 * 
 * @return string Форма
 */
function getSendForm(string $__isAuthorised)
{
    $html = '<div class="inner">
                <div class="send_a">
                    <form method="POST" name="sendFormA">';
    
    //Поле с именем для неавторизованных пользователей
    if ($__isAuthorised == false) {
        $html .= '<input type="text" name="name" placeholder="Имя">';
    }
    
    //Поле для ввода ссобщения
    $html .= '<textarea placeholder="Введите сообщение" name="message"></textarea>
             <button type="submit"> Отправить </button>
             </form> </div> </div>';
    return $html; 
}
