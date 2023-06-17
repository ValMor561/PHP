<?php
session_start();
$messageOnPage = 5;

//Авторизация
if (isset($_POST["login"]) && isset($_POST["password"])) {
    $fileName = "users.txt";
    touch($fileName);
    $usersArr = unserialize(file_get_contents($fileName));    
    $login = rtrim(($_POST["login"]), " ");
    $password = rtrim(($_POST["password"]), " ");
    if (key_exists($login, $usersArr) && md5($password) === $usersArr[$login]) {
        $_SESSION['user'] = $login;
    }
    
    //перенаправляю скрипт
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
    $name = isset($_POST['name']) ? ($_POST['name']) : $_SESSION['user'];
    $message = rtrim(($_POST['message']), "\n\r");
    if ($message != "") {   
        $address = $_SERVER['REMOTE_ADDR'];
        $time = date('Y-m-d H:i:s');
        $str = "[:|||:]".$name."[:||:]".$message."[:||:]".$time."[:||:]".$address."[:|||:]\n";
        file_put_contents("guestbook.txt", $str, FILE_APPEND);
        header("Location: http://".$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME']);
    }
}

//удаления сообщения
if (isset($_POST['delete'])) {
    touch("guestbook.txt");
    $str = file_get_contents("guestbook.txt");
    $messages = arrayMessage($str);
    $message = $messages[$_POST['delete']];
    $strToReplace = "[:|||:]".$message[0]."[:||:]".$message[1]."[:||:]".$message[2]."[:||:]".$message[3]."[:|||:]\n";
    $str = str_replace($strToReplace, "", $str);
    file_put_contents("guestbook.txt", $str);
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

//Считываю из файла
touch("guestbook.txt");
$str = file_get_contents("guestbook.txt");
$messages = arrayMessage($str);

//вывожу html
$html = getHeader();
$html .= (isAuthorised()) ? getLogoutForm(isAuthorised()).getSendForm(isAuthorised()) : getLoginForm().getSendForm(isAuthorised());
if (count($messages) > 0) {
    $html .= getPage($messages, $messageOnPage, $pageNow);
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
 * 
 * @return string Форма
 */
function getPage(array $__messages, int $__messageOnPage, int $__selectedPage)
{
    $html = '<div class="inner" id="last_block">
            <div class="message_page">';
    $html .= getMessages($__messages, $__messageOnPage, $__selectedPage);    
    $html .= '<form method="POST" class="page_list">
                    <ul>';
    $countPages = ceil(count($__messages) / $__messageOnPage);
    
    //вывожу кнопки
    for ($index = 1; $index < $countPages + 1; ++$index) {       
        $html .= '<li><button ';
        
        //выбранная страница
        if ($index == $__selectedPage) {
            $html .= 'id="selected"';
        }
        
        //просто страницы
        $html .= 'name="page" value="'.$index.'">'.$index.'</button></li>';
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
 * 
 * @return string Форма
 */
function getMessages(array $__messages, int $__messageOnPage, int $__selectedPage)
{
    $html = "";
    $startMessage = ($__selectedPage - 1) * $__messageOnPage;
    $endMessage = $startMessage + $__messageOnPage < count($__messages) ? $startMessage + $__messageOnPage : count($__messages);
    for ($index = $startMessage; $index < $endMessage; ++$index) {
        $html .= '<div class="message">
                    <form method="POST">
                    <p class="name">'.$__messages[$index][0];
        
        //если админ или пользватель который написал сообщение добавляю кнопку удалит
        if (isset($_SESSION['user']) && ($_SESSION['user'] == $__messages[$index][0] || $_SESSION['user'] == "admin")) {
            $html .= '<button type="submit" name="delete" value="'.$index.'"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" 
                fill="currentColor" class="bi_bi-trash" viewBox="0 0 16 16">
                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 
                        0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                        <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 
                        1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4L4 4.059V13a1 1 0 0 0 1 
                        1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                    </svg> </button>';
        }   
        
        //Добавляю сообщение и время
        $html .= '</p class="text">
                    <p>'.$__messages[$index][1].'</p>
                    <p class="time">'.$__messages[$index][2]." ";
        if (isset($_SESSION['user']) && ($_SESSION['user'] == "admin")) {
            $html .= '<i>'.$__messages[$index][3].'</i>';
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
    $messages = explode("[:|||:]\n", $__str);
    array_pop($messages);
    foreach ($messages as $lines => $message) {
        $message = str_replace("[:|||:]", "", $message);
        $messages[$lines] = explode("[:||:]", $message);
    }
    
    //Разворачиваю массив чтобы сообщения были в обратном порядке
    $messages = array_reverse($messages);
    return ($messages);
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
