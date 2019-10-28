<?php


namespace GenSys\GenerateBundle\Service;


use ReflectionMethod;

class FileService
{
    private const REGEX_CLASSNAME = '/class\s([a-zA-Z0-9]*)\n\{/';
    private const REGEX_NAMESPACE = '/namespace\s([a-zA-Z0-9\\\\]*);/';
    
    /**
     * @param $fileName
     * @return string
     */
    public function getClassNameFromFile($fileName): string
    {
        $contents = file_get_contents($fileName);

        /**
         * Get namespace from file contents through regex
         */
        $matches = [];
        preg_match(self::REGEX_NAMESPACE, $contents, $matches);
        $namespace = $matches[1];

        /**
         * Get classname from file contents trough regex
         */
        $matches = [];
        preg_match(self::REGEX_CLASSNAME, $contents, $matches);
        $className = $matches[1];

        return $namespace . '\\' . $className;
    }

    public function getReflectionMethodBody(ReflectionMethod $reflectionMethod): string
    {
        $filename = $reflectionMethod->getFileName();
        $startLine = $reflectionMethod->getStartLine() + 1;
        $endLine = $reflectionMethod->getEndLine() - 1;
        $length = $endLine - $startLine;

        $source = file($filename);
        return implode('', array_slice($source, $startLine, $length));
    }
}