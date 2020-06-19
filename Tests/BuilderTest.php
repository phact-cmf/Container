<?php declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

class BuilderTest extends TestCase
{
    /**
     * Проверка что при передаче самого простого Definition без зависимостей - объект корректно создается
     * Проверка что при передаче Definition c зависимостями - объект корректно создается
     * Проверка что при передаче Definition c зависимостями и аргументами - объект корректно создается
     * Проверка что при передаче Definition + constructionMethod - объект корректно создается
     * Проверка что при передаче Definition + constructionMethod + зависимости - объект корректно создается
     * Проверка что при передаче Definition + constructionMethod + зависимости + аргументы - объект корректно создается
     * ---
     * Проверка что при передаче аргументов конструктора ассоциативным массивом - всё установится согласно именам
     * Проверка что при передаче аргументов конструктора простым массом - всё установится согласно позиции в массиве
     * Проверка что при передаче аргументов со ссылкой на обязательный компонент он будет корректно установлен
     * Проверка что при передаче аргументов со ссылкой на опциональный компонент он будет корректно установлен,
     *  если имеется в контейнере
     * Проверка что при передаче аргументов со ссылкой на опциональный компонент он не будет установлен без ошибки
     * ---
     * Проверка работы с callable-фабрикой (анонимная функция) - объект корректно создается
     * Проверка работы с callable-фабрикой + зависимости (анонимная функция) - объект корректно создается
     * Проверка работы с invokable-фабрикой, указанной строкой как идентификатор объекта в контейнере
     * Проверка работы с фабрикой, указанной строкой как идентификатор объекта в контейнере + constructMethod
     * Проверка работы с фабрикой, указанной как массив - класс + метод
     * Проверка работы с фабрикой, указанной как массив - идентификатор + метод
     * ---
     * Проверка что при передаче Definition со свойствами - свойства корректно устанавливаются
     * Проверка что при передаче Definition с методами - методы корректно вызываются
     * ---
     * Проверка что inflect корректно устанавливает свойства объекта
     * Проверка что inflect корректно вызывает методы к объекта
     * --
     * Проверка что invoke корректно отработает без зависимостей
     * Проверка что invoke корректно отработает с зависимостями - они будут установлены
     * Проверка что invoke корректно отработает с зависимостями и аргументами - всё будет установлено
     * ---
     * Exception на некорректную фабрику
     * Exception если не найдена необходимая зависимость конструктора
     * Exception если не найдена необходимая зависимость constructMethod
     * Exception если не найдена необходимая зависимость фабрики
     */
}
