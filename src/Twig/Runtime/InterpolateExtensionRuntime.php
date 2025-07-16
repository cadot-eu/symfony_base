<?php

namespace App\Twig\Runtime;

use Twig\Extension\RuntimeExtensionInterface;

class InterpolateExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct() {}

    public function interpolate(array $context, string $text): string
    {
        return preg_replace_callback('/{{\s*([\w\.]+)\s*}}/', function ($matches) use ($context) {
            $keys = explode('.', $matches[1]);
            $value = $context;

            foreach ($keys as $key) {
                if (is_array($value) && array_key_exists($key, $value)) {
                    $value = $value[$key];
                } elseif (is_object($value)) {
                    // Essaie d'abord avec un getter (ex: getId)
                    $getter = 'get' . ucfirst($key);
                    if (method_exists($value, $getter)) {
                        $value = $value->$getter();
                    } elseif (method_exists($value, '__get')) {
                        $value = $value->$key; // Accès via __get magique
                    } else {
                        return $matches[0]; // Ne peut pas accéder → laisser tel quel
                    }
                } else {
                    return $matches[0]; // Pas accessible
                }
            }

            return $value;
        }, $text);
    }
}
