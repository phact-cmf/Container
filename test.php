<?php

require 'vendor/autoload.php';
$classes = [];
$i = 100;
while ($i > 0) {
    $classes = array_merge($classes, get_declared_classes());
    $i--;
}

$start = microtime(true);

$parents = [];
$out = 0;
foreach ($classes as $class) {
    $ref = new ReflectionClass($class);
    $constructor = $ref->getConstructor();
    if ($constructor) {
        foreach ($constructor->getParameters() as $param) {
            $parents[] = [
                $param->isVariadic(),
                $param->getName(),
                $param->getClass() ? ($param->getClass())->getName() : null,
                $param->allowsNull(),
                $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
            ];
        }
    } else {
        $out++;
    }
//    $parents[] = $ref;
//    $parents[] = class_parents($class);
//    $parents[] = class_implements($class);
}

print_r(microtime(true) - $start);
echo PHP_EOL;
print_r(number_format(memory_get_peak_usage(), 0, '.', ' '));
var_dump($out);