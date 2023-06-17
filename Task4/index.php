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
    $out .= myPrint(myLinkParse($link))."<br>";
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
function myLinkParse(string $__link)
{
    $isError = false;
    $parseArr;
    if (strpos($__link, "://") < strpos($__link, "/") && strpos($__link, "://") !== false) {

        //нахожу протокол, заношу его в массив и удаляю из основной ссылки
        $parseArr['protocol'] = substr($__link, 0, strpos($__link, "://"));
        $__link = str_replace($parseArr['protocol']."://", "", $__link);

        //работа с доменом
        $domain = substr($__link, 0, strpos($__link, "/"));

        //проверяю содержится ли порт в ссылке
        $port  = (strlen($domain) - strpos($domain, ':') < strlen($domain) && strlen($domain) - strpos($domain, ':') > 1) 
            ? substr($domain, strrpos($domain, ':') + 1) 
            : 80;

        //обрезаю порт если он есть
        if (strpos($domain, ':') !== false) {
            $domain = substr($domain, 0, strpos($domain, ':'));
        }

        //заношу в массив и удаляю домен из исходной ссылки
        $__link = str_replace($domain, "", $__link);
        $parseArr['domain'] = $domain;

        //заношу в массив и удаляю зону из домена
        $parseArr['zone'] = substr($domain, strrpos($domain, '.') + 1);
        $domain = str_replace(".".$parseArr['zone'], "", $domain);
        $parseArr['2_level_domain'] = str_replace(".", "", substr($domain, strrpos($domain, ".")));
        $parseArr['port'] = $port;
        if (substr_count($domain, '.') > 4) {
            $isError = true;
        }

        //узнаю значение ссылки
        $rawFolder = substr($__link, strpos($__link, '/') + 1, strpos($__link, '?') - strpos($__link, '/') - 1);
    }
    else {
        $parseArr['domain'] = false;
        $rawFolder = substr($__link, 0, strpos($__link, "?"));
    }

    //работа с путями
    $__link = str_replace($rawFolder, "", $__link);
    $pathLink = transformFolder($rawFolder);

    //удаляю название скрипта из ссылки если он есть там
    if (strlen($rawFolder) - strpos($rawFolder, "/") > 1 && strpos($rawFolder, "/") !== false) {
        $rawFolder = substr($rawFolder, 0, strrpos($rawFolder, "/") + 1);
    }
    
    //убираю ссылку если нет путя
    if (strpos($rawFolder, "/") === false) {
        $rawFolder = "";
    }

    //заношу в массив знаение ссылки и её сокращения
    $parseArr['raw_folder'] = $rawFolder;
    $folder = transformFolder($rawFolder);
    $parseArr['folder'] = $folder;

    //проверяю есть ли название скрипта если нет то задаю его
    if (strrpos($pathLink, '.') > strrpos($pathLink, '/')) {
        $parseArr['script_path'] = $pathLink;
    }
    else {
        if (strpos($pathLink, '/') === false) {
            $parseArr['script_path'] = $pathLink."/index.php";
        }
        else {
            $parseArr['script_path'] = $pathLink."index.php";
        }
    }

    //заношу в массив значение имени и проверяю является ли файл php
    $parseArr['script_name'] = strpos($parseArr['script_path'], '/') !== false 
        ? substr($parseArr['script_path'], strrpos($parseArr['script_path'], '/') + 1) 
        : $parseArr['script_path'];
    $parseArr['is_php'] = strpos($parseArr['script_name'], '.php') === false ? "false" : "true";
    $parameters = substr($__link, strpos($__link, '?') + 1);
    $parseArr['parameters'] = findParameters($parameters);
    $parseArr['is_error'] = $isError ? "true" : "false";
    return $parseArr;
}

/**
 * Функция для нахождения параметров и их значений в строке
 *
 * @param string $__parameters Строка в которой надо искать параметры.
 *
 * @return array Массив параметров и их значений
 */
function findParameters(string $__parameters)
{
    $parametersArr;
    while (substr_count($__parameters, "=") != 0) {

        //выделяю подстроку которая между знаками ?
        $subparameters = strpos($__parameters, '?') !== false 
            ? substr($__parameters, 0, strpos($__parameters, '?')) 
            : $__parameters;
        $__parameters = strpos($__parameters, '?') !== false 
            ? str_replace($subparameters."?", "", $__parameters) 
            : str_replace($subparameters, "", $__parameters);

        //разбиваю подстроку на параметры и их значения
        while (substr_count($subparameters, "=") != 0) {
            $var = substr($subparameters, 0, strpos($subparameters, "="));
            $subparameters = str_replace($var."=", "", $subparameters);
            $val = strpos($subparameters, "&") !== false 
                ? substr($subparameters,0,strpos($subparameters, "&")) 
                : $subparameters;
            $subparameters = str_replace($val."&", "", $subparameters);
            $parametersArr[$var] = $val;
        }
    }

    //возвращаю Массив параметров и их значений
    return $parametersArr;
}

/**
 * Функция сокращения ссылки
 *
 * @param string $__folder Исходная ссылка.
 *
 * @return string Сокращенная ссылка
 */
function transformFolder(string $__folder)
{
    
    //убираю /./
    while (strpos($__folder, '/./') !== false) {
        $__folder = str_replace('/./', '/', $__folder);
    }
    
    //убираю //
    while (strpos($__folder, '//') !== false) {
        $__folder = str_replace('//', '/', $__folder);
    }
    
    //убираю /../
    while (strpos($__folder, '/../') !== false) {
        $newFolder = substr($__folder, 0, strpos($__folder, '/../'))."<br>";
        $__folder = substr_replace($__folder, '', strrpos($newFolder, '/'), strpos($__folder, '/../') - strrpos($newFolder, '/') + 3);
    }
    
    //удалаяю ведущий / если он есть
    if (strpos($__folder, "/") === 0) {
        $__folder = substr($__folder, 1);
    }
    
    //возвращаю сокращенную ссылку
    return $__folder;
}

