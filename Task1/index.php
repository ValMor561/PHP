<?php
// строка для вывода
$output = "";

// вывод чисел от 1 до 10
for ($index = 1; $index <= 10; ++$index) {
    $output .= fibonacci($index)." ";
}

// вывод чисел от 1 до 10
$output .= "</br>";
for ($index = 1; $index <= 10; ++$index) {
    $output .= fibonacciRecursive($index)." ";
}

//вывод
print ($output);

/** 
 * Функция вычисления числа фибоначи не рекурсией 
 * Не совсем это имелось ввиду: сделать алгоритм рекурсивным (в задании так и написано),
 * но завернуть рекурсию в цикл.
 * 
 * @param integer $__index Номер в последовательности.
 * 
 * @return integer член последовательности Фибоначчи с номером $__index
 */
function fibonacci(int $__index)
{
    $previous = 0;
    $next = 1;
    for ($index = 1; $index <= $__index; ++$index) {
        $now = $next;
        $next = $previous + $now;
        $previous = $now;
    }
    
    //возвращаю значени
    return $now;
}

/** 
 * Функция вычисления числа фибоначи рекурсией 
 * 
 * @param integer $__index Номер в последовательности.
 * 
 * @return integer член последовательности Фибоначчи с номером $__index
 */
function fibonacciRecursive(int $__index)
{
    if ($__index < 1) {
        return 0;
    }
    
    //начальные значения
    if ($__index == 1) {
        return 1;
    }
    
    //рекурсия
    return fibonacciRecursive($__index - 1) + fibonacciRecursive($__index - 2);
}
