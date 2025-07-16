<?php

namespace App\Service;

class PromptBuilderService
{
    public function buildPrompt(array $data, string $extra = ''): string
    {
        $addRoute = !empty($data['add_route']);
        $addDocblock = !empty($data['add_docblock']);
        $routeInstruction = $addRoute
            ? "Ajoute l'attribut #[Route('/nom-de-ta-route', name: 'auto_route')] juste au-dessus de la méthode générée, comme pour une action de contrôleur Symfony 6+."
            : "";
        $docblockInstruction = $addDocblock
            ? "Ajoute un docblock PHP complet et explicatif juste au-dessus de la méthode générée (et de la route si présente), qui décrit ce que fait la méthode, ses paramètres et ce qu'elle retourne."
            : "";
        return sprintf(
            "%s\n%s\n%s\n%s\n\n" .
                "Réponds STRICTEMENT avec deux blocs de code PHP distincts, sans aucun texte autour, dans ce format :\n" .
                "```php\n// méthode à ajouter dans App\\%s\npublic function %s(%s)\n{\n    // ...\n}\n```\n" .
                "```php\n// test PHPUnit pour cette méthode\npublic function test%s()\n{\n    \$service = new \\%s();\n    // ...\n}\n```\n" .
                "Dans le bloc du test, instancie TOUJOURS la classe cible avec son FQCN (exemple : \$service = new \\App\\Service\\MyService();). " .
                "N'utilise jamais de namespace, de use, ni de balise <?php dans les blocs. " .
                "La première réponse doit être la méthode seule, la seconde le test seul. " .
                "Exemple :\n" .
                "```php\npublic function foo(int \$a)\n{\n    return \$a + 1;\n}\n```\n" .
                "```php\npublic function testFoo()\n{\n    \$service = new \\App\\Service\\MaClasse();\n    \$this->assertEquals(2, \$service->foo(1));\n}\n```\n",
            "Ajoute dans le fichier {$data['file']} une méthode nommée {$data['method']} avec la signature ({$data['params']}). But : {$data['goal']}.",
            $extra,
            $routeInstruction,
            $docblockInstruction,
            str_replace('/', '\\', $data['file']),
            $data['method'],
            $data['params'],
            ucfirst($data['method']),
            'App\\' . str_replace(['/', '.php'], ['\\', ''], $data['file'])
        );
    }
}
