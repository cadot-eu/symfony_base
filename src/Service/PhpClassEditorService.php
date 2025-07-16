<?php

namespace App\Service;

class PhpClassEditorService
{
    public function extractPhpCodeBlocks(string $content): array
    {
        preg_match_all('/```php\s*(.*?)```/is', $content, $matches);
        return array_map('trim', $matches[1]);
    }

    public function extractNamedMethod(string $code, string $methodName): ?string
    {
        if (preg_match('/(public|protected|private)\s+function\s+' . preg_quote($methodName, '/') . '\s*\([^\)]*\)\s*(:\s*[a-zA-Z0-9_\\\]+)?\s*\{(?:[^{}]++|(?R))*\}/s', $code, $matches)) {
            return $matches[0];
        }
        return null;
    }

    public function removeMethodFromClass(string $classContent, string $methodName): string
    {
        $pattern = '/(public|protected|private)\s+function\s+' . preg_quote($methodName, '/') . '\s*\([^\)]*\)\s*(?::\s*[a-zA-Z0-9_\\\\]+)?\s*\{/';
        while (preg_match($pattern, $classContent, $matches, PREG_OFFSET_CAPTURE)) {
            $start = $matches[0][1];
            $openBraces = 0;
            $end = $start;
            $inString = false;
            for ($i = $start; $i < strlen($classContent); $i++) {
                $char = $classContent[$i];
                if ($char === '"' || $char === "'") {
                    $inString = !$inString;
                }
                if (!$inString) {
                    if ($char === '{') {
                        $openBraces++;
                    } elseif ($char === '}') {
                        $openBraces--;
                        if ($openBraces === 0) {
                            $end = $i + 1;
                            break;
                        }
                    }
                }
            }
            $classContent = substr($classContent, 0, $start) . substr($classContent, $end);
        }
        $classContent = preg_replace("/\n{3,}/", "\n\n", $classContent);
        return $classContent;
    }

    public function removeRouteAttributesBeforeMethod(string $classContent, string $methodName): string
    {
        $pattern = '/((?:\s*#\[\s*Route\s*\([^\)]*\)\s*\]\s*)+)(\s*(public|protected|private)\s+function\s+' . preg_quote($methodName, '/') . '\s*\()/';
        return preg_replace($pattern, '$2', $classContent);
    }

    public function removeDocblockBeforeMethod(string $classContent, string $methodName): string
    {
        $pattern = '/((?:\s*\/\*\*.*?\*\/\s*)+)(\s*(public|protected|private)\s+function\s+' . preg_quote($methodName, '/') . '\s*\()/s';
        return preg_replace($pattern, '$2', $classContent);
    }
}
