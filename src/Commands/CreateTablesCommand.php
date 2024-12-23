<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Commands;

use JuanchoSL\Backups\Engines\Packagers\TarEngine;
use JuanchoSL\Orm\Engine\DbCredentials;
use JuanchoSL\Orm\Engine\Enums\EngineEnums;
use JuanchoSL\Orm\Engine\Factory;
use JuanchoSL\Orm\Querybuilder\QueryBuilder;
use JuanchoSL\Terminal\Command;
use JuanchoSL\Terminal\Contracts\InputInterface;
use JuanchoSL\Terminal\Enums\InputArgument;
use JuanchoSL\Terminal\Enums\InputOption;

class CreateTablesCommand extends Command
{

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
        $this->addArgument('type', InputArgument::REQUIRED, InputOption::SINGLE);
        $this->addArgument('tables', InputArgument::OPTIONAL, InputOption::MULTI);
        $this->addArgument('exclude', InputArgument::OPTIONAL, InputOption::MULTI);
        $this->addArgument('destiny', InputArgument::REQUIRED, InputOption::SINGLE);
    }

    protected function execute(InputInterface $input): int
    {
        $credentials = new DbCredentials($input->getArgument('host'), $input->getArgument('user'), $input->getArgument('pass'), $input->getArgument('database'));
        $connection = Factory::connection($credentials, EngineEnums::tryFrom($input->getArgument('type')));
        if (!empty($this->logger)) {
            $connection->setLogger($this->logger);
            $connection->setDebug($this->debug);
        }
        $tables_backup = $input->getArgument('destiny') . DIRECTORY_SEPARATOR . 'create_tables_'.date("YmdHis").'.tar';
        $this->log("Set file global destiny: '{destiny}'", 'debug', ['destiny' => $tables_backup]);

        $tar = new TarEngine();
        $tar->setDestiny($tables_backup);

        $tables = $input->hasArgument('tables') ? $input->getArgument('tables') : $connection->getTables();
        $this->log("Extract tables to process", 'debug', ['tables' => $tables]);

        foreach ($tables as $table) {
            if ($input->hasArgument('exclude') && in_array($table, $input->getArgument('exclude'))) {
                $this->log("Excluded table '{table}'", 'debug', ['table' => $table]);
                continue;
            } else {
                $this->log("Included table '{table}'", 'debug', ['table' => $table]);
            }
            $table_backup = $input->getArgument('destiny') . DIRECTORY_SEPARATOR . 'table_' . $table . '.sql';
            $this->log("Set file table destiny: '{destiny}'", 'debug', ['destiny' => $table_backup]);
            $file = fopen($table_backup, 'w+');
            $description = $connection->describe($table);
            fwrite($file, $connection->query(QueryBuilder::getInstance()->create(...$description)->table($table)));
            fclose($file);
            $tar->addFile($table_backup, $table . '.sql');
            unlink($table_backup);
        }
        $tar->close();
        unlink($tables_backup);
        $this->log("Deleted temporal backup file '{tables_backup}'", ['tables_backup' => $tables_backup]);
        return 0;
    }
}