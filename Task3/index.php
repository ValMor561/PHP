<?php
// исходный массив
$wordArraysArr = array(
    array("aaa", "bvvvhhf"),
    array("a", "eeeee", "vvv"),
    array("gokt", "ac", "wwwww", "iegnv"),
    array("mvmv", "qgmpqmok"),
);

//вызываю функцию вывода представленную нижу
print (printArr($wordArraysArr));

/**
 * Форматированный вывод массива
 * 
 * @param array $__arrays Исходный массив.
 * 
 * @return sting Строка с отформатированным массивом 
 */
function printArr(array $__arrays)
{
    $output = "";
    $output .= "<pre>";
    $countOfWordInArray = MaxCount($__arrays);
    $newArr = modifyingAnArray($__arrays);
    
    //вывожу массивы в столбец 
    //мне здесь нужен for т.к количество слов в каком-то массиве может быть больше чем в моем и мне нужно дополнить пробелами
    for ($col = 0; $col < $countOfWordInArray; ++$col) { 
        foreach ($__arrays as $row => $arr) { 
            
            //проверяю есть не вышел ли я за границу массива
            if (array_key_exists($col, $newArr[$row])) {
                $output .= $newArr[$row][$col]."  ";
            }
            
            //если вышел вывожу слово состоящее из пробелов длинна которого равна длине наибольшего слова
            else {
                $countOfSpaces = maxLengthOfWords($newArr[$row]);
                $output .= str_repeat(" ", $countOfSpaces + 2);
            }
        }
        $output .= "\n";
    }
    
    //закрываю тег и возвращаю отформатированный массив 
    $output .= "</pre>";
    return $output;
}

/**
 * Определяет наибольшую длинну слова среди массива
 * 
 * @param array $__array Любой массив.
 * 
 * @return integer Длинна
 */
function maxLengthOfWords(array $__array)
{
    $maxLength = 0;
    foreach ($__array as $word) {
        $length = mb_strlen($word);
        if ($length > $maxLength) {
            $maxLength = $length;
        }
    } 
    
    //возрат наибольшей длинны
    return $maxLength;
}

/**
 * Расширяет все слова в массиве до самого длинного засчет пробелов
 * 
 * @param array $__array Исходный массив.
 * 
 * @return array Отформатированный
 */
function modifyingAnArray(array $__array)
{
    foreach ($__array as $col => $colWord) {
        $length = maxLengthOfWords($colWord);
        foreach ($colWord as $row => $rowWord) {
            if ($col % 2 != 0) {
                $__array[$col][$row] = str_pad($colWord[$row], $length, " ", STR_PAD_LEFT);
            } 
            else {
                $__array[$col][$row] = str_pad($colWord[$row], $length, " ", STR_PAD_RIGHT);
            }
        }
    }
  
    //возвращаю исправленный массив
    return $__array;
}

/**
 * Ищю максимальное количество слов в подмасивах
 * 
 * @param array $__array Исходный массив.
 * 
 * @return integer Количество слов
 */
function maxCount(array $__array)
{
    $maxCount = 0;
    foreach ($__array as $arr) {
        $count = count($arr);
        if ($count > $maxCount) {
            $maxCount = $count;
        }
    }
    
    //возвращаю количество слов
    return $maxCount;
}
