<?php

namespace App\Twig\Runtime;

use Twig\Extension\RuntimeExtensionInterface;
use ReflectionClass;

class EncodeRuntime implements RuntimeExtensionInterface
{


    public function encode($object)
    {
        $data = [];

        if (method_exists($object, 'map')) {
            // c'est une collection symfony
            $object = $object->map(function ($v) {
                if (method_exists($v, '__toString')) {
                    return (string) $v;
                }
                return $v;
            })->toArray();
        }

        if (is_object($object)) {
            $refClass = new ReflectionClass($object);
            foreach ($refClass->getProperties() as $property) {
                $property->setAccessible(true); // Accède même aux private/protected
                $name = $property->getName();
                $value = $property->getValue($object);

                // Optionnel : sérialiser récursivement les objets simples
                if (is_object($value) && method_exists($value, '__toString')) {
                    $value = (string) $value;
                }

                $data[$name] = $value;
            }
        } else {
            $data = $object;
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
