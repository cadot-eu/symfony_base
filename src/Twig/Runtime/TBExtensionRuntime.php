<?php

namespace App\Twig\Runtime;

use Twig\Extension\RuntimeExtensionInterface;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Cloner\ClonerInterface;

class TBExtensionRuntime implements RuntimeExtensionInterface
{


    public function __construct() {}

    public function dd($value)
    {
        //on met se code en haut de la page html en créant une div fixed
        echo '<div style="position: fixed; background-color: white; top: 0; left: 0; z-index: 9999; padding: 10px; border: 1px solid black;"><pre>';
        dd($value);
    }
    public function is_numeric($value)
    {
        return is_numeric($value);
    }
    public function ddump(mixed $var): string
    {
        $cloner = new VarCloner();
        $dumper = new HtmlDumper();

        $output = '';
        $dumper->dump($cloner->cloneVar($var), function (string $line) use (&$output) {
            $tab = '';
            //si la ligne finis par [ on ajoute une tabulation aux ligne suivantes 
            if (!in_array(substr($line, 0, 1), ["+"])) {
                $tab = "  ";
            }

            $output .= $tab . $line . "\n";
        });

        return <<<HTML
<div style="background:#f6f6f6;border:1px solid #ccc;padding:10px;margin:10px 0;overflow:auto;font-size:0.9em;">
$output
</div>
HTML;
    }
}
