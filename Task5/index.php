<?php
$error = "";
$result = "";
$systemBase = "dec";

//массив со системами счисления
$mySystemArr = array( 
    "bin"   => "01",
    "oct"   => "01234567",
    "dec"   => "0123456789",
    "hex"   => "0123456789ABCDEF",
    "mybin" => "xy"
);

//обработка значений
if (isset($_POST["button"])) {
    $isError = false;
    $systemBase = $_POST["baseSystem"]; 
    $secondHasMinus = false;
    $firstHasMinus = false;
    $resultHasMinus = false;
        
    //беру систему 
    $baseArr = array();
    $systemLength = mb_strlen($mySystemArr[$systemBase]);
    for ($index = 0; $index < $systemLength; ++$index) {
        $number = substr($mySystemArr[$systemBase], $index, 1);
        $basesArr[] = $number;
    }
    
    //перевожу символы системы счисления в верхний регистр
    foreach ($basesArr as $key => $letter) {
        $basesArr[$key] = strtoupper($letter);
    }
    
    //проверяю есть ли минус у первого
    if (substr($_POST["firstNum"], 0, 1) == "-") {
        $firstHasMinus = true;
    }
    
    //проверяю есть ли минус у второго
    if (substr($_POST["secondNum"], 0, 1) == "-") {
        $secondHasMinus = true;
    }
    
    //проверяю есть ли минус у результата
    if (substr($_POST["result"], 0, 1) == "-") {
        $resultHasMinus = true;
    }
    
    
    //обрабатыва параметры
    
    //если введениы и первый и второй
    if ($_POST["firstNum"] !== "" && $_POST["secondNum"] !== "") {
        $firstNum = ltrim(strtoupper($_POST["firstNum"]), ' "/<>-');
        $secondNum = ltrim(strtoupper($_POST["secondNum"]), ' "/<>-'); 
    }
    
    //если есть результат но нет одного из параметров
    else if ($_POST["firstNum"] === "" && $_POST["secondNum"] !== "" && $_POST["result"] !== "") {
        $firstNum = ltrim(strtoupper($_POST["result"]), ' "/<>-'); 
        $firstHasMinus = $resultHasMinus;
        $secondNum = ltrim(strtoupper($_POST["secondNum"]), ' "/<>-');
    }
    else if ($_POST["firstNum"] !== "" && $_POST["secondNum"] === "" && $_POST["result"] !== "") {
        $firstNum = ltrim(strtoupper($_POST["result"]), ' "/<>-'); 
        $firstHasMinus = $resultHasMinus;
        $secondNum = ltrim(strtoupper($_POST["firstNum"]), ' "/<>-');
    }
    
    //если не хватает параметров
    else {
        $firstNum = "";
        $secondNum = "";
        $isError = true;
        $error = "Недостаточно параметров";
    }
    
    //обработка оператора
    $symbol = $_POST["button"];
    switch ($symbol) {
        case '+' : $operation = "plus"; break;
        case '-' : $operation = "minus"; break;
        case '*' : $operation = "multiplication"; break;
        case '/' : $operation = "division"; break;
    }
    
    //обработка ошибок  
    
    //проверяю все ли символы 1-го слагаемого есть в системе счисления
    $firstLength = mb_strlen($firstNum);
    for ($index = 0; $index < $firstLength; ++$index) {
        substr($firstNum, $index, 1);
        if (in_array(substr($firstNum, $index, 1), $basesArr) === false) {
            $isError = true;
        }
    }
    
    //проверяю все ли символы 1-го слагаемого есть в системе счисления
    $secondLength = mb_strlen($secondNum);
    for ($index = 0; $index < $secondLength; ++$index) {
        if (in_array(substr($secondNum, $index, 1), $basesArr) === false) {
            $isError = true;
        }
    }
    
    //вывожу сообщение об ошибке
    if ($isError && $error == "") {
        $error = "Данные не соответсвуют выбранной системе счиления";
    }
    
    //ошибка при делении
    if ($secondNum == 0 && $operation == "division") {
        $isError = true;
        $error = "Деление на 0";
    }
    //выполнеие операций    
    
    //сложение
    if ($operation == "plus" && !$isError) {
        $first = convertToDec($firstNum, $firstHasMinus, $basesArr);
        $second = convertToDec($secondNum, $secondHasMinus, $basesArr);
        $result = reverseTransformation($first + $second, $basesArr);
    }

    //разность
    if ($operation == "minus" && !$isError) {
        $first = convertToDec($firstNum, $firstHasMinus, $basesArr);
        $second = convertToDec($secondNum, $secondHasMinus, $basesArr);
        $result = reverseTransformation($first - $second, $basesArr);
    }
    
    //умножение
    if ($operation == "multiplication" && !$isError) {
        $first = convertToDec($firstNum, $firstHasMinus, $basesArr);
        $second = convertToDec($secondNum, $secondHasMinus, $basesArr);
        $result = reverseTransformation($first * $second, $basesArr);
    }
    
    //умножение
    if ($operation == "division" && !$isError) {
        $first = convertToDec($firstNum, $firstHasMinus, $basesArr);
        $second = convertToDec($secondNum, $secondHasMinus, $basesArr);
        $result = reverseTransformation(floor($first / $second), $basesArr);
    }
}

//вывожу форму
$html = '<!DOCTYPE html>
            <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <title> Калькулятор </title>
                    <link rel="stylesheet" href="style.css">
                </head>
                <body>
                    <div class="container">
                        <div class="calc">
                            <form method="post">
                                <select name="baseSystem" required>';

//вывожу системы счислений
foreach ($mySystemArr as $base => $value) {
    $html .= '<option value="'.$base.'"';
    if ($base == $systemBase) {
        $html .= "selected";
    }   
    
    //добавляю название системы для выбора
    $html .= '>'.$base.'</option>';
}

//вывожу остальное
$html .= '</select>    
            <p><input type="text" name="firstNum"></p>
            <p><input type="text" name="secondNum"></p>
            <p><input name=button type="submit" value="+" id="button"> <input name=button type="submit" value="-" id="button"></p>
            <p><input name=button type="submit" value="*" id="button"> <input name=button type="submit" value="/" id="button"></p>
            <p><textarea name="result" readonly>'.$result.'</textarea> </p>
            <p><textarea name="errors" readonly>'.$error.'</textarea> </p>
            </form>
            </div> 
            </div>    
        </body>
        </html>';

//вывожу форму
print ($html);

/**
 * Функция переводит строку в число в десятеричной системе счисления
 * 
 * @param string  $__number     Исходная строка.
 * @param boolean $__isHasMinus Есть минус у числа или нет.
 * @param array   $__basesArr   Cистема счисления.
 * 
 * @return integer Число в десятеричной системе
 */
function convertToDec(string $__number, bool $__isHasMinus, array $__basesArr)
{
    $result = 0;
    $length = strlen($__number);
    $count = 0;
    $base = count($__basesArr);
    
    //перевожу в десятичною систему счисления
    for ($index = $length - 1; $index >= 0; --$index) {
        $numeral = array_search(substr($__number, $index, 1), $__basesArr, true);
        $result += pow($base, $count) * $numeral;
        ++$count; 
    } 
    
    //рассматриваю случай когда отрицательное число
    if ($__isHasMinus) {
        $result = 0 - $result;
    }
    
    //возвращаю число в десятичной системе счисления
    return $result;
}

/**
 * Обратное преобразование числа из десятичной системы в нужную
 * 
 * @param integer $__number  Число в десятичной системе.
 * @param array   $__baseArr База системы.
 * 
 * @return string Результат в нужной системе
 */
function reverseTransformation(int $__number, array $__baseArr)
{
    $result = "";
    $base = count($__baseArr);
    
    //проверяю отрицательное число или нет
    if ($__number < 0) {
        $minus = true;
        $__number = 0 - $__number;
    }
    else {
        $minus = false;
    }
    
    //перевожу в десятичную систему счисления
    while ($__number >= $base) {
        $result .= $__baseArr[$__number % $base];
        $__number = floor($__number / $base);
    }
    
    //добавляю остаток и минус если надо
    $result .= $__baseArr[$__number];
    if ($minus) {
        $result .= "-";
    }
    
    //возвращаю нужное число
    $result = strrev($result);
    return $result;
}
