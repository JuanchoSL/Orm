<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Commands;

use JuanchoSL\Backups\Engines\Packagers\TarEngine;
use JuanchoSL\Backups\Engines\Packagers\ZipEngine;
use JuanchoSL\Backups\Strategies\BackupDated;
use JuanchoSL\Logger\Composers\TextComposer;
use JuanchoSL\Logger\Logger;
use JuanchoSL\Logger\Repositories\FileRepository;
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
        $this->addArgument('tables', InputArgument::REQUIRED, InputOption::MULTI);
        $this->addArgument('destiny', InputArgument::REQUIRED, InputOption::SINGLE);
    }

    protected function execute(InputInterface $input): int
    {
        $credentials = new DbCredentials($input->getArgument('host'), $input->getArgument('user'), $input->getArgument('pass'), $input->getArgument('database'));
        $connection = Factory::connection($credentials, EngineEnums::tryFrom($input->getArgument('type')));
        if(!empty($this->logger)){
            $connection->setLogger($this->logger);
            $connection->setDebug($this->debug);
        }
        $tar = new TarEngine();
        $tar->setDestiny($input->getArgument('destiny') . DIRECTORY_SEPARATOR . 'datas.tar');
        $tables = $input->getArgument('tables');
        foreach ($tables as $table) {
            $table_backup = $input->getArgument('destiny') . DIRECTORY_SEPARATOR . 'data_' . $table . '.sql';
            $file = fopen($table_backup, 'w+');
            $cursor = $connection->execute(QueryBuilder::getInstance()->select()->from($table));
            while (!empty($element = $cursor->next())) {
                fwrite($file, $connection->query(QueryBuilder::getInstance()->insert((array) $element)->into($table)) . ';' . PHP_EOL);
            }
            $cursor->free();
            fclose($file);
            $tar->addFile($table_backup, $table . '.sql');
            unset($table_backup);
        }
        $tar->close();
        return 0;
    }
}