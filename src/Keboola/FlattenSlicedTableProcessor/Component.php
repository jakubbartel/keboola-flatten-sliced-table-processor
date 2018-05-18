<?php

namespace Keboola\FlattenSlicedTableProcessor;

use Keboola\Component\BaseComponent;
use Keboola\Csv\InvalidArgumentException;

class Component extends BaseComponent
{

    /**
     * @return string
     */
    protected function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }

    /**
     *
     * @throws \Keboola\Csv\Exception
     */
    public function run() : void
    {
        $processor = new Processor($this->getManifestManager());

        try {
            $processor->processDir(
                sprintf('%s%s', $this->getDataDir(), '/in/tables'),
                sprintf('%s%s', $this->getDataDir(), '/out/tables')
            );
        } catch(InvalidArgumentException $e) {
        } catch(Exception\UserException $e) {
        }
    }

}
