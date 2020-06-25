# Dependency Injection Container

Следует стандартам PSR-11, а также PSR-1, PSR-2, PSR-4.

Основные идеи:
- Создание объектов, указанных в конфигурации
- Создание объектов, не указанных в конфигурации (в том числе рекурсивно - на любой уровень вложенности)
- Возможность осуществления вызовов и установки свойств после создания объекта
- Возможность осуществления вызовов и установки свойств по классу/интерфейсу (например, "Aware"-интерфейсы)
- Создание объектов с помощью фабрик
- Алиасы (псевдонимы, теги) для любого сервиса
- Отключаемая возможность анализа зависимостей методов/конструкторов с помощью рефлексии
- Возможность добавить дочерние контейнеры для получения объектов, не описанных в текущем контейнере

## Основные возможности контейнера

Создание объекта контейнера:

```php
$container = new Container();
```

С помощью указания собственных реализаций зависимостей контейнера вы можете расширять или изменять его функциональность.
@TODO: ссылку на [Возможность изменения поведения контейнера](#).

### Добавление описания сервиса - addDefinition

Добавление описания требует указания уникального имени добавляемого сервиса.

```php
$definition = new Definition(SimpleComponent::class);
$definition = $container->addDefinition('simple', $definition);
```

Далее, по указанному имени может быть:
- указана ссылка на описание сервиса
- произведено получение объекта из контейнера
- определено соответствие любого класса и описанного сервиса
- произведено добавление псевдонима к описанию сервиса


### Быстрое добавление описания сервиса - addDefinitionClass

Является аналогом метода ```addDefinition```, но позволяет вместо объекта ```Definition``` передать имя класса:

```php
$definition = $container->addDefinitionClass('simple', SimpleComponent::class);
```

### Добавление скалярного значения - addScalar

Добавление любого значения в контейнер для последующего использования:

```php
$container->addScalar('main_email', 'some@email.com');
```

При попытке получить значение из контейнера по имени вернется указанное значение:

```php
$email = $container->get('main_email');
// $email = 'some@email.com'
```
### Определение соответствия запрашиваемого класса и описанного сервиса - addReference

Возможность указать, что при запросе определенного класса будет возращен объект по описанию, добавленному ранее.

Например, мы добавили описание нашего простейшего сервиса:

```php
$definition = new Definition(SimpleComponent::class);
$definition = $container->addDefinition('simple', $definition);
```

И хотим чтобы при запросе класса ```SimpleComponentInterface::class``` возвращался бы объект, 
созданный с помощью описания (Definition), который мы добавили ранее. 

Указываем соответствие класса ```SimpleComponentInterface::class``` и Definition:

```php
$container->addReference('simple', SimpleComponentInterface::class);
```

Теперь, при запросе объекта по класс ```SimpleComponentInterface::class``` контейнер будет возвращать объект, 
созданный с помощью указанного описания (Definition). 

### Добавление псевдонима (тега) к существующему описанию сервиса - addAlias, addAliases

Возможность указать псевдоним/псевдонимы для существующего описания по имени.

Например, мы добавили описание нашего простейшего сервиса:

```php
$definition = new Definition(SimpleComponent::class);
$definition = $container->addDefinition('simple', $definition);
```

И хотим чтобы при запросе по имени "simple_object" возвращался бы объект, 
созданный с помощью описания (Definition), который мы добавили ранее. 

Добавляем псевдоним:

```php
$container->addAlias('simple', 'simple_object');
```

Теперь, при запросе объекта по имени "simple_object" контейнер будет возвращать объект, 
созданный с помощью указанного описания (Definition). 

Так же поддерживается добавление нескольких псевдонимов (тегов) одновременно:

```php
$container->addAlias('simple', [
    'simple_object',
    'smart'
]);
```

### Добавление конфигурирования объектов определенного вида - addInflection

Позволяют конфигурировать созданные объекты определенного вида:
- Устанавливать свойства
- Выполнять методы

Весьма удобны с "Aware" - интерфейсами.

```php
$inflection = new Inflection(LoggerAwareInterface::class);
$inflection->addCall('setLogger', $myLogger);
$container->addInflection($inflection);
```

### Добавление дочернего контейнера - addDelegate

Позволяет извлекать сервисы из дочерних контейнеров, если они не найдены в текущем

```php
$container->addDelegate($anotherContainer);
```

### Возможность вызова callable с автоматической инъекцией зависимостей - invoke

Осуществляет вызов callable с инъекцией необходимых зависимостей.

Пример класс:

```php
class Example {
    public $simpleComponent;

    public function setSimpleComponent(SimpleComponent $simpleComponent)
    {
        $this->simpleComponent = $simpleComponent;
    }
}
```

Вызов:

```
$example = new Example();
$container->invoke([$example, 'setSimpleComponent']);
// $example->simpleComponent instanceof SimpleComponent = true
```

С передачей аргументов:

```
$example = new Example();
$container->invoke([$example, 'setSimpleComponent'], [
    'simpleComponent' => new SimpleComponent()
]);
// $example->simpleComponent instanceof SimpleComponent = true
```

### Получение объекта - get

Получение объекта по имени, алиасу (псевдониму/тегу), классу, родительским классам или интерфейсам.

```
$simpleComponent = $container->get(SimpleComponent::class);
$exampleComponent = $container->get('example');
```

### Проверка на возможность получения объекта - has

Проверка на то, присутствует ли объект/описание в контейнере либо в дочерних контейнерах.

```
$hasSimpleComponent = $container->has(SimpleComponent::class);
$hasExampleComponent = $container->has('example');
```

## Объект описания сервиса - Definition

Необходим для описания конфигурирования создания объектов.

### Создание объекта Definition

Обязательным параметром конструктора является имя класса, объект которого будет создан.

```
$definition = new Definition(SimleComponent::class);
```

### Указание аргументов конструктора - setArguments

Установка аргументов конструктора.

Все не переданные аргументы со значениями по-умолчанию будут заменены на значения по-умолчанию.
Все не переданные аргументы с указанием определенного класса будут извлечены из контейнера.

```
$definition->setArguments([
    'username' => 'admin',
    'password' => 'mypassword'
]);
```

@TODO: Так же, в аргументах конструктора можно (использовать ссылки на описания других сервисов)[#]

```
$definition->setArguments([
    'simpleService' => '@simple'
]);
```

### Указание установки свойства после создания объекта - addProperty

Установка свойств будет осуществлена после создания объекта.

```php
$definition->addProperty('username', 'admin');
$definition->addProperty('password', 'mypassword');
```

### Указание вызова метода после создания объекта - addCall

Вызов метода будет осуществлен после создания объекта и установки свойств

```php
$definition->addCall('setLogin', 'admin');
```

@TODO: Так же, в аргументах вызова метода можно (использовать ссылки на описания других сервисов)[#]

```php
$definition->addCall('setSimpleService', '@simple');
```

### Добавление псевдонима (тега) - addAliases, addAlias

> Важно! Необходимо устанавливать псевдонимы (теги) до передачи описания (Definition) в контейнер.

Впоследствии, по указанным псевдонимам (тегам) можно будет ссылаться на описание сервиса, либо получать объект из контейнера.

Указание псевдонима (тега):

```php
$definition->addAlias('another_name');
```

или (сразу несколько):

```php
$definition->addAliases(['another_name', 'second_name']);
```

### Указание метода-конструктора - setConstructMethod

Можно указать, если для создания объекта необходимо вызвать статический метод класса.

```php
class Example {
    public static function create()
    {
        return new Example();
    }
}
```

```php
$definition = new Definition(Example::class);
$definition->setConstructMethod('create');
```

Данный метод так же применяется и для работы с фабриками.

### Указание фабрики для создания объекта - setFactory

@TODO

#### Callable

@TODO

#### Invokable - ссылка на описание компонента

@TODO

#### Invokable-объект

@TODO

#### Ссылка на описание и constructorMethod

@TODO

#### Имя класса и constructorMethod

@TODO

#### Массив - ссылка на описание и метод

@TODO

#### Массив - имя класса и метод

@TODO


### Указание, что объект является "общим" (shared) - setShared

> Внимание! По-умолчанию со стандартным описанием (Definition) объект считается "общим" (shared) 

Если необходимо, чтобы объект создался один раз при первом запросе, 
а впоследствии возвращался один и тот же экземпляр объекта (например, подключение к базе данных),
то можно указать что объект является "общим":

```php
$definition = new Definition(DatabaseConnection::class);
$definition->setShared(true);
```

Если нужно указать, что при каждом запросе объекта должен возвращаться новый экземпляр, 
то вызываем метод ```setShared``` с параметром ```false```:

```php
$definition = new Definition(Mail::class);
$definition->setShared(false);
```

## Объект конфигурирования объектов определенного вида - Inflection

Контейнер позволяет конфигурировать (вызывать методы и устанавливать свойства) объектам одного типа.

Описанием такой конфигурации является объект Inflection.

Конструктор принимает класс, которому должен соответствовать созданный объект для применения конфигурации:

```php
$inflection = new Inflection(SimpleInterface::class);
```

После создания объекта контейнер обойдет все объекты конфигурации и применит подходящие из них.

### Указание установки свойства после создания объекта - addProperty

Установка свойств буде осуществлена в момент применения объекта конфигурации.

```php
$inflection->addProperty('username', 'admin');
$inflection->addProperty('password', 'mypassword');
```

### Указание вызова метода после создания объекта - addCall

Вызов методов будет осуществлен в момент применения объекта конфигурации после применения свойств.

```php
$inflection->addCall('setLogin', 'admin');
```

@TODO: Так же, в аргументах вызова метода можно (использовать ссылки на описания других сервисов)[#]

```php
$inflection->addCall('setSimpleService', '@simple');
```

## Использование ссылок на описания сервисов

@TODO: через собаку - где и как, с примерами 

### В аргументах методов и конструкторов

@TODO

### В фабриках

@TODO

## Управление анализом предков и интерфейсов добавляемых классов

@TODO

## Управление созданием объектов, не описанных через объект Definition

@TODO

## Управление анализом аргументов методов и конструкторов

@TODO

## Возможность изменения поведения контейнера

@TODO 