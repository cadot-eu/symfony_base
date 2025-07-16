<?php

namespace App\Twig\Runtime;

use Twig\Extension\RuntimeExtensionInterface;

class ArrayToListExtensionRuntime implements RuntimeExtensionInterface
{
    public function ArrayToList($array): string
    {
        //on créé un html ul li
        $html = '<ul>';
        foreach ($array as $key => $value) {
            $html .= '<li>' . htmlspecialchars($key) . ': ' . htmlspecialchars($value) . '</li>';
        }
        $html .= '</ul>';
        return $html;
    }
}
