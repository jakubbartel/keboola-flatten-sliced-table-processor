<?php

namespace Keboola\FlattenSlicedTableProcessor\Tests\Processor;

use Keboola\FlattenSlicedTableProcessor;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class ProcessorTest extends TestCase
{

    public function testProcessOneFile() : void
    {
        $manifestManager = new \Keboola\Component\Manifest\ManifestManager(__DIR__ . '/fixtures/onefile');

        $processor = new FlattenSlicedTableProcessor\Processor($manifestManager);

        $fileSystem = vfsStream::setup();

        $processor->processFile(
            __DIR__ . '/fixtures/onefile/in/tables',
            'input.csv',
            $fileSystem->url()
        );

        $this->assertEquals(
            scandir($fileSystem->url()),
            [
                '.',
                '..',
                'input-Berlin.csv',
                'input-Bratislava.csv',
                'input-Budapest.csv',
                'input-Prague.csv',
            ]
        );

        $this->assertFileEquals(
            __DIR__ . '/fixtures/onefile/out/tables/input-Berlin.csv',
            $fileSystem->url() . '/input-Berlin.csv'
        );

        $this->assertFileEquals(
            __DIR__ . '/fixtures/onefile/out/tables/input-Bratislava.csv',
            $fileSystem->url() . '/input-Bratislava.csv'
        );

        $this->assertFileEquals(
            __DIR__ . '/fixtures/onefile/out/tables/input-Budapest.csv',
            $fileSystem->url() . '/input-Budapest.csv'
        );

        $this->assertFileEquals(
            __DIR__ . '/fixtures/onefile/out/tables/input-Prague.csv',
            $fileSystem->url() . '/input-Prague.csv'
        );
    }

    public function testDashedFilename() : void
    {
        $manifestManager = new \Keboola\Component\Manifest\ManifestManager(__DIR__ . '/fixtures/dash');

        $processor = new FlattenSlicedTableProcessor\Processor($manifestManager);

        $fileSystem = vfsStream::setup();

        $processor->processFile(
            __DIR__ . '/fixtures/dash/in/tables',
            'input-1.csv',
            $fileSystem->url()
        );

        $this->assertEquals(
            [
                '.',
                '..',
                'input--1-Prague--Center.csv',
            ],
            scandir($fileSystem->url())
        );

        $this->assertFileEquals(
            __DIR__ . '/fixtures/dash/out/tables/input--1-Prague--Center.csv',
            $fileSystem->url() . '/input--1-Prague--Center.csv'
        );
    }

}
