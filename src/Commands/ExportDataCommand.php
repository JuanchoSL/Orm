<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Commands;

use JuanchoSL\Backups\Engines\Packagers\ZipEngine;
use JuanchoSL\Backups\Strategies\BackupDated;
use JuanchoSL\Backups\Strategies\BackupUnique;
use JuanchoSL\Orm\Commands\Traits\FunctionsTrait;
use JuanchoSL\Orm\Engine\DbCredentials;
use JuanchoSL\Orm\Engine\Enums\EngineEnums;
use JuanchoSL\Orm\Engine\Factory;
use JuanchoSL\Orm\Querybuilder\QueryBuilder;
use JuanchoSL\Terminal\Command;
use JuanchoSL\Terminal\Contracts\InputInterface;
use JuanchoSL\Terminal\Enums\InputArgument;
use JuanchoSL\Terminal\Enums\InputOption;

class ExportDataCommand extends Command
{

    use FunctionsTrait;

    public function getName(): string
    {
        return "exportdata";
    }

    protected function configure(): void
    {
        $this->addArgument('host', InputArgument::REQUIRED, InputOption::SINGLE);
        $this->addArgument('user', InputArgument::REQUIRED, InputOption::SINGLE);
        $this->addArgument('pass', InputArgument::REQUIRED, InputOption::SINGLE);
        $this->addArgument('database', InputArgument::REQUIRED, InputOption::SINGLE);
        $this->addArgument('driver', InputArgument::REQUIRED, InputOption::SINGLE);
        $this->addArgument('tables', InputArgument::OPTIONAL, InputOption::MULTI);
        $this->addArgument('exclude', InputArgument::OPTIONAL, InputOption::MULTI);
        $this->addArgument('destiny', InputArgument::REQUIRED, InputOption::SINGLE);
        $this->addArgument('copies', InputArgument::OPTIONAL, InputOption::SINGLE);
        $this->addArgument('basename', InputArgument::OPTIONAL, InputOption::SINGLE);
    }

    protected function execute(InputInterface $input): int
    {
        $credentials = new DbCredentials($input->getArgument('host'), $input->getArgument('user'), $input->getArgument('pass'), $input->getArgument('database'));
        $connection = Factory::connection($credentials, EngineEnums::tryFrom($input->getArgument('driver')));
        if (!empty($this->logger)) {
            $connection->setLogger($this->logger);
            $connection->setDebug($this->debug);
        }

        $tables = $input->hasArgument('tables') ? $input->getArgument('tables') : $connection->getTables();
        $this->log("Extract tables to process", 'debug', ['tables' => $tables]);

        $destiny = $input->getArgument('destiny');
        $tmp = $destiny . DIRECTORY_SEPARATOR . 'tmp';
        if (!file_exists($tmp)) {
            mkdir($tmp, 0777, true);
        }
        foreach ($tables as $table) {
            if ($input->hasArgument('exclude') && in_array($table, $input->getArgument('exclude'))) {
                $this->log("Excluded table '{table}'", 'debug', ['table' => $table]);
                continue;
            } else {
                $this->log("Included table '{table}'", 'debug', ['table' => $table]);
            }
            $table_backup = $tmp . DIRECTORY_SEPARATOR . 'data_' . $table . '.sql';
            $this->log("Set file table destiny: '{destiny}'", 'debug', ['destiny' => $table_backup]);
            $file = fopen($table_backup, 'w+');
            $cursor = $connection->execute(QueryBuilder::getInstance()->select()->from($table));
            while (!empty($element = $cursor->next())) {
                fwrite($file, $connection->query(QueryBuilder::getInstance()->insert((array) $element)->into($table)) . ';' . PHP_EOL);
            }
            $cursor->free();
            fclose($file);
        }

        $obj = ($input->hasArgument('copies')) ? new BackupDated : new BackupUnique;
        $obj->setEngine(new ZipEngine());
        $obj->setDestinationFolder($destiny);
        if ($input->hasArgument('copies')) {
            $obj->setNumBackups((int) $input->getArgument('copies'));
        }
        $basename = $input->hasArgument('basename') ? $input->getArgument('basename') : 'datas';
        $obj->pack($tmp, $basename);
        $this->deleteDirRecursive($tmp);
        return 0;
    }
}