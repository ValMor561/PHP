<?php
$testArr = array(
    "http://http.ru/folder/subfolder/../././script.php?var1=val1&var2=val2",
    "https://http.google.com/folder//././?var1=val1&var2=val2",
    "ftp://mail.ru/?hello=world&url=https://http.google.com/folder//././?var1=val1&var2=val2",
    "mail.ru/?hello=world&url=https://http.google.com/folder//././?var1=val1&var2=val2",
    "index.html?mail=ru",
    "domain2.zone:8080/folder/subfolder/../././../asdss/.././//////../myfolder/script.php?var1=val1&var2=val2",
    "http://dom.dom.domain2.com:8080/folder/subfolder/./myfolder/script.php?var1=val1&var2=val2?var1=val2&var2=val1",
);
$out = "<pre>";

//перебираю массив и добавляю его в строку
foreach ($testArr as $number => $link) {
    $out .= "<b>".$link."</b><br>";
    $out .= myPrint(myLinkPsreParse($link))."<br>";
}

//вывожу строку со значениями всех массивов
$out .= "</pre>";
print $out;

/**
 * Функция формирования из массива строки для вывода
 *
 * @param array $__parseLink Распаршенный массив.
 *
 * @return string Строка для вывода
 */
function myPrint(array $__parseLink)
{
    $output = "";

    //Перебираю все значения массива и вывожу их
    foreach ($__parseLink as $key => $value) {
        if (is_array($value)) {
            $output .= $key." => ";

            //Вывожу массив параметров
            foreach ($value as $parameters => $parametersValue) {
                $output .= $parameters." => ".$parametersValue.", ";
            }

            //
            $output .= "<br>";
        }
        else {
            $output .= $key." => ".$value.'<br>';
        }
    }

    //возвращаю строку для вывода
    return $output;
}

/**
 * Функция парсинга ссылки
 *
 *  @param string $__link Сслыка.
 *
 *  @return array Массив с отдельными элементами ссылки
 */
function myLinkPsreParse(string $__link)
{
    //инициализирую массив
    $resultArr = array(
        'protocol'         => false,
        'domain'           => false,
        'zone'             => false,
        'two_level_domain' => false,
        'port'             => false,
        'raw_folder'	   => false,
        'folder'           => false,
        'script_path'      => false,
        'script_name'      => false,
        'is_php'           => false,
        'parameters'	   => array(),
        'is_error'         => false,
    );

    //разбираю ссылку
    preg_match(
        "|
        (?:(?<protocol>[[:alnum:]]*)[:]//)?    #протокол
        (?<domain>                             #домен
        (?<two_level_domain>[[:alnum:]]*\.)+   #поддомен
        (?<zone>[[:alnum:]]*))?                #зона
        [:]?(?<port>[[:alnum:]]*)?             #порт
        /?(?<raw_folder>(?:[[:alnum:]\.]*/)*)? #путь
        (?<script_name>[[:alnum:]]*\.          #имя скрипта
        (?<is_php>[[:alnum:]]*))?              #расширение
        \?(?<parameters>\S*)?                   #параметры
        |xis", $__link, $parseArr
    );

    // очищаю массив полученный регуляркой
    foreach ($parseArr as $key => $value) {
        $parseArr[$key] = $parseArr[$key] === "" ? false : $parseArr[$key];
        if (gettype($key) == "integer") {
            unset($parseArr[$key]);
        }
    }

    // объединяю масссив, полученный регуляркой и массив, полученный вне регулярки
    $result = array_merge($resultArr, $parseArr);

    // заполняю домен 2-ого уровня
    $result['two_level_domain'] .= $result['zone'];

    // если протокол есть, то обрабатываю путь
    if ($result['protocol'] != false) {
        parseFolder($result['raw_folder'], $result);

        // если находимся в корневой папке
        if ($result['raw_folder'] == false) {
            $result['raw_folder'] = '/';
        }
    }
    else{

        // обрабытываю путь
        parseFolder($result['raw_folder'], $result);

        // если протокол отсутствует, то url определяется как относительный путь
        $result['raw_folder']       = $result['domain'].$result['port'].'/'.$result['raw_folder'];
        $result['domain']           = false;
        $result['zone']             = false;
        $result['two_level_domain'] = false;
        $result['port']             = false;
    }

    // если количество поддоменов > 5, устанавливаем флаг ошибки
    $result['is_error'] = substr_count($result['domain'], ".") > 5 ? "true" : "false";

    // определяем путь до сценария
    if ($result['script_name'] !== false) {
        $result['script_path'] = $result['folder'].$result['script_name'];
    }

    // путь до сценария по умолчанию
    else {
        $result['script_path'] = substr($result['folder'], 0, -1).'/index.php';
    }

    // указываем папку сценария
    $allFolder = preg_split("@/@", $result['folder']);
    if (count($allFolder) > 2) {
        $result['folder'] = $allFolder[array_key_last($allFolder) - 1].'/';
    }

    // определяем имя сценария по умолчанию, если есть параметры
    if ($result['script_name'] === false && $result['parameters'] !== false) {
        $result['script_name'] = "index.php";
    }

    // имя сценария, если задано
    else {
        $result['script_name'] = $result['script_name'];
    }

    // если скрипт с расширение .php, то is_php = true
    $result['is_php'] = $result['is_php'] == 'php' || $result['script_name'] == 'index.php' ? "true" : "false";

    // регулярка разбивает параметры на массив из параметров и их значений
    preg_match_all('|([^=]*)[=]([^&?]*)\&?\??|uis', $result['parameters'], $parseParameter);
    $result['parameters'] = array_combine($parseParameter[1], $parseParameter[2]);

    // удаляю вспомогательную группу и возвращаю массив
    unset($result['port']);
    return $result;
}


/**
 * Функция обрабатывающая путь
 *
 * @param string $__path      Путь до сценария со сценарием.
 * @param array  $__resultArr Результирующий массив.
 *
 * @return void
 */
function parseFolder(string $__path, array &$__resultArr)
{
    // разбиваю путь на папки
    $allFolder = preg_split("@/@", $__path);

    // начинаем двигаться по папкам
    $iterator = 0;
    foreach ($allFolder as $nesting => $folderName) {
        if ($allFolder[$nesting] != ".." && $allFolder[$nesting] != "." && $allFolder[$nesting] != "") {
            $resultFolder[$iterator] = $allFolder[$nesting];
            $iterator++;
        }

        // если '..', то шаг назад путем удаления последнего элемента массива
        elseif ($allFolder[$nesting] != "." && $allFolder[$nesting] != "") {
            unset($resultFolder[$iterator - 1]);
            $iterator--;
        }
    }

    // обнуляю значение пути
    $__resultArr["folder"] = false;

    // при попытке выйти за доменное имя выводим корневую папку
    if (isset($resultFolder)) {
        if (count($resultFolder) == 0) {
            $__resultArr["folder"] = '/';
        }

        // обрабатываем путь
        else {
            foreach ($resultFolder as $nesting => $folderName) {
                $__resultArr["folder"] .= $resultFolder[$nesting].'/';
            }
        }
    }
}
