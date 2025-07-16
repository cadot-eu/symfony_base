<?php

namespace App\Twig\Runtime;

use Twig\Extension\RuntimeExtensionInterface;

class DataExtensionRuntime implements RuntimeExtensionInterface
{
    public function Data($json, $explode = false)
    {
        $blocks = json_decode($json)->blocks;
        if (empty($blocks)) {
            return [];
        }
        if (sizeof($blocks) == 1) {
            if ($explode) {
                return explode($explode, $blocks[0]->data->text);
            }
            return $blocks[0]->data->text;
        }
        $dataArray = [];
        foreach ($blocks as $block) {
            $dataArray[] = [
                'type' => $block->type,
                'data' => $block->data->text
            ];
        }

        return $dataArray;
    }
}
