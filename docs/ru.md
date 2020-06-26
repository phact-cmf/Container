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
[Возможность изменения поведения контейнера](#возможность-изменения-поведения-контейнера).

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

Также, в аргументах конструктора можно [использовать ссылки на описания других сервисов](#использование-ссылок-на-описания-сервисов).

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

Также, в аргументах метода можно [использовать ссылки на описания других сервисов](#использование-ссылок-на-описания-сервисов).

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

Поддерживается указание фабрики для создания объектов.

#### Callable

Пример:

```php
$definition = new Definition(Example::class);
$definition->setFactory(function () {
    return new Example();
});
```

#### Invokable - ссылка на описание компонента

Фабрика:

```php
class ExampleFactory {
    public function __invoke(){
        return new Example();
    }
}
```

Контейнер:

```php
$container->addDefinitionClass('exampleFactory', ExampleFactory::class);

$definition = new Definition(Example::class);
$definition->setFactory('@exampleFactory');
```

#### Invokable-объект

Фабрика:

```php
class ExampleFactory {
    public function __invoke(){
        return new Example();
    }
}
```

Контейнер:

```php
$definition = new Definition(Example::class);
$definition->setFactory(new ExampleFactory());
```

#### Ссылка на описание и constructMethod

Фабрика:

```php
class ExampleFactory {
    public function createExample(){
        return new Example();
    }
}
```

Контейнер:

```php
$container->addDefinitionClass('exampleFactory', ExampleFactory::class);

$definition = new Definition(Example::class);
$definition->setFactory('@exampleFactory');
$definition->setConstructMethod('createExample');
```

#### Имя класса и constructMethod

Фабрика:

```php
class ExampleFactory {
    public function createExample(){
        return new Example();
    }
}
```

Контейнер:

```php
$container->addDefinitionClass('exampleFactory', ExampleFactory::class);

$definition = new Definition(Example::class);
$definition->setFactory(ExampleFactory::class);
$definition->setConstructMethod('createExample');
```

#### Массив - ссылка на описание и метод

Фабрика:

```php
class ExampleFactory {
    public function createExample(){
        return new Example();
    }
}
```

Контейнер:

```php
$container->addDefinitionClass('exampleFactory', ExampleFactory::class);

$definition = new Definition(Example::class);
$definition->setFactory(['@exampleFactory', 'createExample']);
```

#### Массив - имя класса и метод

Фабрика:

```php
class ExampleFactory {
    public function createExample(){
        return new Example();
    }
}
```

Контейнер:

```php
$container->addDefinitionClass('exampleFactory', ExampleFactory::class);

$definition = new Definition(Example::class);
$definition->setFactory([ExampleFactory::class, 'createExample']);
```


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

Также, в аргументах метода можно [использовать ссылки на описания других сервисов](#использование-ссылок-на-описания-сервисов).

```php
$inflection->addCall('setSimpleService', ['@simple']);
```

## Использование ссылок на описания сервисов

### В аргументах методов и конструкторов

В аргументах методов и конструкторов можно указывать ссылки на тот или иной сервис.
Это осуществляется передачей в качестве параметра строки, в которой содержится символ "@" и 
далее следует имя или псевдоним (тег) сервиса.

Например, в контейнере есть описание нескольких сервисов:
 
```php
$container->addDefinitionClass('fileLogger', Logger::class);
$container->addDefinitionClass('mailLogger', Logger::class);
```

Есть класс, который требует объект в качестве зависимости:

```php
class Example 
{
    public function __construct(Logger $logger) 
    {
        // ...
    }
}
``` 

В конструктор класса можно передать ссылку на определенный сервис:

```php
$definition = new Definition(Example::class);
$definition->setArguments([
    'logger' => '@fileLogger'
]);
```

При создании объекта ```Example``` в качестве логгера будет установлен объект, 
который создан по описанию (Definition) с именем "fileLogger".

Аналогичным образом работает указание ссылок в аргументах метода (и для Definition и для Inflection):

```php
$definition->addCall('setLogger', [
    'logger' => '@fileLogger'
]);
```

Если по переданному имени невозможно получить объект, будет выброшено исключение.

Для того чтобы указать опциональную зависимость, необходимо перед именем (псевдонимом/тегом) указать "@?".

Например, имеется метод:

```php
class Example 
{
    public function setLogger(Logger $logger = null) 
    {
        // ...
    }
}
```

В описании сервиса укажем:

```php
$definition->addCall('setLogger', [
    'logger' => '@?fileLogger'
]);
```

В таком случае если компонент по имени (псевдониму/тегу) будет найден - он будет подставлен. 
Если он не будет найден - будет подставлено значение по-умолчанию.

### Ссылка на сервис в фабриках

Ссылку на сервис можно так же использовать и при указании фабрики для создания объекта.

Например в этом примере для создания объекта будет использоваться 
метод ```createObject``` сервиса с именем (тегом/псевдонимом) "myFactory":

```php
$definition->setFactory('@myFactory');
$definition->setConstructMethod('createObject');
```

Другой вариант (массив - ссылка на сервис и его метод):

```php
$definition->setFactory(['@myFactory', 'createObject']);
```

Или (если "myFactory" является Invokable объектом):

```php
$definition->setFactory('@myFactory');
```

## Управление анализом предков и интерфейсов добавляемых классов

По-умолчанию Container (а точнее его часть - DefinitionAggregate) автоматически анализирует 
все родительские классы и интерфейсы добавляемого сервиса.

Если у контейнера будет запрошен объект по интерфейсу или родительскому классу, 
то вернется соответствующий объект.

Например, есть класс:

```php
class Example extends ExampleParent implements ExampleInterface 
{
    // ...
}
```

Добавим его в контейнер:

```php
$definition = new Definition(Example::class);
$container->addDefinition('example', $definition);
```

Теперь, при попытке получить объект по классам ```ExampleParent::class``` и ```ExampleInterface::class```
будет получен объект созданный по описанию, добавленному нами выше:

```php
$object = $container->get(ExampleParent::class);
// get_class($object) = Example::class

$object = $container->get(ExampleInterface::class);
// get_class($object) = Example::class
```

Отключить данное поведение можно следующим образом:

```php
$definitionAggregate = new DefinitionAggregate();
$definitionAggregate->setAnalyzeReferences(false);

$container = new Container(null, $definitionAggregate);
```

## Управление созданием объектов, не описанных через объект Definition

По-умолчанию контейнер будет пытаться автоматически создать необходимые объекты, даже если они не были описаны в контейнере.

Например, имеем класс:

```php
class Example 
{
    public function setLogger(Logger $logger = null) 
    {
        // ...
    }
}
```

Добавим его описание в контейнер:

```php
$definition = new Definition(Example::class);
$container->addDefinition('example', $definition);
```

При попытке получить объект ```Example::class``` контейнер попытается найти описание подходящего класса, 
необходимого для создания объекта (в нашем случае это ```Logger::class```).

Если найти подходящее описание невозможно, контейнер попытается создать этого класса.

Отключить это поведение можно следующим образом:

```php
$container = new Container();
$container->setAutoWire(false);
```

## Управление анализом аргументов методов и конструкторов

По-умолчанию Container (а точнее его часть - Builder) анализирует все аргументы методов и конструкторов, 
что позволяет автоматически подставлять необходимые зависимости.

Отключить это поведение можно следующим образом:

```php
$builder = new Builder(false);
$container = new Container($builder);
```

## Возможность изменения поведения контейнера

Структура контейнера:

- Container
    - BuilderInterface
        - DependenciesResolverInterface
    - DefinitionAggregateInterface

Все зависимости являются заменяемыми и расширяемыми.