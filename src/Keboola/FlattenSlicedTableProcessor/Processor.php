<?php declare(strict_types = 1);

namespace Keboola\FlattenSlicedTableProcessor;

use Keboola\Component\Manifest\ManifestManager;
use Keboola\Csv;
use Keboola\FlattenSlicedTableProcessor\Exception\UserException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Processor
{

    /**
     * @var ManifestManager
     */
    private $manifestManager;

    /**
     * Processor constructor.
     *
     * @param ManifestManager $manifestManager
     */
    public function __construct(ManifestManager $manifestManager)
    {
        $this->manifestManager = $manifestManager;
    }

    /**
     * Look up all file names that are directories witch csv extension and have a manifest.
     *
     * @param string $inFilesDirPath
     * @return array
     */
    private function getFilesToProcess(string $inFilesDirPath): array
    {
        $files = [];

        $finder = new Finder();
        $finder->directories()->in($inFilesDirPath)->name('*.csv');

        foreach($finder as $file) {
            $manifestPath = $file->getPathname() . '.manifest';

            if(!file_exists($manifestPath)) {
                continue;
            }

            $files[] = $file->getBasename();
        }

        return $files;
    }

    /**
     * @param string $fileName
     * @return string
     */
    private function deconflictFileName(string $fileName): string
    {
        return str_replace('-', '--', $fileName);
    }

    /**
     * @param string $sourcePath
     * @param string $destinationPath
     * @param string[] $header
     * @return Processor
     * @throws Csv\InvalidArgumentException
     * @throws Csv\Exception
     * @throws Exception\FileAppendException
     */
    private function copyCsvWithHeader(string $sourcePath, string $destinationPath, array $header): self
    {
        return $this
            ->createCsvHeader($destinationPath, $header)
            ->appendCsvBody($sourcePath, $destinationPath);
    }

    /**
     * @param string $destinationPath
     * @param array $header
     * @return Processor
     * @throws Csv\Exception
     * @throws Csv\InvalidArgumentException
     */
    private function createCsvHeader(string $destinationPath, array $header): self
    {
        $csvFile = new Csv\CsvFile($destinationPath);

        $csvFile->writeRow($header);

        unset($csvFile);

        return $this;
    }

    /**
     * @param string $sourcePath
     * @param string $destinationPath
     * @return Processor
     * @throws Exception\FileAppendException
     */
    private function appendCsvBody(string $sourcePath, string $destinationPath): self
    {
        $cmd = sprintf("cat %s >> %s", $sourcePath, $destinationPath);

        $process = new Process($cmd);
        try {
            $process->mustRun();
        } catch(ProcessFailedException $e) {
            throw new Exception\FileAppendException(sprintf("Cannot append to output file, \"%s\"", $e->getMessage()));
        }

        return $this;
    }

    /**
     * @param string $inFileDirPath
     * @param string $slicedFileName
     * @param string $outFilesDirPath
     * @return Processor
     * @throws UserException
     * @throws Csv\InvalidArgumentException
     * @throws Csv\Exception
     * @throws Exception\FileAppendException
     */
    public function processFile(string $inFileDirPath, string $slicedFileName, string $outFilesDirPath): self
    {
        $slicedFilePathName = sprintf('%s/%s', $inFileDirPath, $slicedFileName);

        $finder = new Finder();
        $finder->in($inFileDirPath)->name($slicedFileName);

        $sliceFinder = new Finder();
        $sliceFinder->files()->in($slicedFilePathName);

        foreach($finder as $slicedFile) {
            foreach($sliceFinder as $slice) {
                $outFilePathName = sprintf('%s/%s-%s.%s',
                    $outFilesDirPath,
                    $this->deconflictFileName($slicedFile->getBasename(sprintf('.%s', $slicedFile->getExtension()))),
                    $this->deconflictFileName($slice->getBasename()),
                    $slicedFile->getExtension()
                );

                $manifest = $this->manifestManager->getTableManifest($slicedFile->getPathname());
                if(!isset($manifest['columns'])) {
                    throw new UserException(
                        sprintf('File\'s "%s" manifest does not contain columns definition', $slicedFile->getPathname())
                    );
                }

                $this->copyCsvWithHeader($slice->getPathname(), $outFilePathName, $manifest['columns']);
            }
        }

        return $this;
    }

    /**
     * @param string $inFilesDirPath
     * @param string $outFilesDirPath
     * @return Processor
     * @throws UserException
     * @throws Csv\InvalidArgumentException
     * @throws Csv\Exception
     * @throws Exception\FileAppendException
     */
    public function processDir(string $inFilesDirPath, string $outFilesDirPath): self
    {
        $slicedFiles = $this->getFilesToProcess($inFilesDirPath);

        foreach($slicedFiles as $file) {
            $this->processFile($inFilesDirPath, $file, $outFilesDirPath);
        }

        return $this;
    }

}
