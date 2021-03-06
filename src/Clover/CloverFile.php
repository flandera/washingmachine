<?php
declare(strict_types=1);

namespace TheCodingMachine\WashingMachine\Clover;

use TheCodingMachine\WashingMachine\Clover\Analysis\Method;

final class CloverFile implements CrapMethodFetcherInterface, CoverageDetectorInterface
{

    /**
     * @var string
     */
    private $fileName;

    /**
     * @var \SimpleXMLElement
     */
    private $root;

    /**
     * @var string
     */
    private $rootDirectory;

    private function __construct()
    {
    }

    public static function fromFile(string $fileName, string $rootDirectory) : CloverFile
    {
        if (!file_exists($fileName)) {
            throw new \RuntimeException('Could not find file "'.$fileName.'". The unit tests did not run or broke before the end, or the file path is incorrect.');
        }

        $cloverFile = new self();
        $cloverFile->fileName = $fileName;
        $errorReporting = error_reporting();
        $oldErrorReporting = error_reporting($errorReporting & ~E_WARNING);
        $cloverFile->root = simplexml_load_file($fileName);
        error_reporting($oldErrorReporting);
        if ($cloverFile->root === false) {
            throw new \RuntimeException('Invalid XML file passed or unable to load file: "'.$fileName.'": '.error_get_last()['message']);
        }
        $cloverFile->rootDirectory = rtrim($rootDirectory, '/').'/';
        return $cloverFile;
    }

    public static function fromString(string $string, string $rootDirectory) : CloverFile
    {
        $cloverFile = new self();
        $errorReporting = error_reporting();
        $oldErrorReporting = error_reporting($errorReporting & ~E_WARNING);
        $cloverFile->root = simplexml_load_string($string);
        error_reporting($oldErrorReporting);
        if ($cloverFile->root === false) {
            throw new \RuntimeException('Invalid XML file passed or unable to load string: '.error_get_last()['message']);
        }
        $cloverFile->rootDirectory = rtrim($rootDirectory, '/').'/';
        return $cloverFile;
    }

    /**
     * @return float
     */
    public function getCoveragePercentage() : float
    {
        $metrics = $this->root->xpath("/coverage/project/metrics");

        if (count($metrics) !== 1) {
            throw new \RuntimeException('Unexpected number of metrics element in XML file. Found '.count($metrics).' elements."');
        }

        $statements = (float) $metrics[0]['statements'];
        $coveredStatements = (float) $metrics[0]['coveredstatements'];

        if ($statements === 0.0) {
            return 0.0;
        }

        return $coveredStatements/$statements;
    }

    /**
     * Returns an array of method objects, indexed by method full name.
     *
     * @return Method[]
     */
    public function getMethods() : array
    {
        $methods = [];
        $files = $this->root->xpath('//file');

        $currentClass = null;
        $currentNamespace = null;

        foreach ($files as $file) {
            foreach ($file as $item) {
                if ($item->getName() === 'class') {
                    $currentClass = (string) $item['name'];
                    $currentNamespace = (string) $item['namespace'];
                } elseif ($item->getName() === 'line') {
                    // <line num="19" type="method" name="__construct" visibility="public" complexity="2" crap="2.03" count="1"/>
                    $type = (string) $item['type'];
                    if ($type === 'method' && $currentClass !== null) {
                        $methodName = (string) $item['name'];
                        $visibility = (string) $item['visibility'];
                        $complexity = (float) $item['complexity'];
                        $crap = (float) $item['crap'];
                        $count = (int) $item['count'];
                        $line = (int) $item['num'];
                        $fileName = (string) $file['name'];

                        if (strpos($fileName, $this->rootDirectory) === 0) {
                            $fileName = substr($fileName, strlen($this->rootDirectory));
                        }

                        $method = new Method($methodName, $currentClass, $currentNamespace, $complexity, $crap, $visibility, $count, $fileName, $line);
                        $methods[$method->getFullName()] = $method;

                    }
                }
            }
        }

        return $methods;
    }
}
