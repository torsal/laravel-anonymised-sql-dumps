<?php

namespace torsal\AnonymisedSQLDumps\Commands;

use Illuminate\Console\Command;
use Ifsnop\Mysqldump as IMysqldump;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Faker\Factory as Faker;

class ExportAnonymisedDB extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anonymised-db-dumps:export {file? : dump file name (without extension}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a SQL dump with anonymised data';

    /**
     * @var \Faker\Generator
     */
    private $faker;
    
    /**
     * @var array
     */
    private $config;

    /**
     * @var array
     */
    private $keys;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {        
        $this->faker = Faker::create(config('app.faker_locale'));
        $this->config = config('db-export');
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $dump = $this->getDumpInstance();
        $dump->setTransformTableRowHook(function ($tableName, array $row) {
            return $this->anonymiseTableRow($tableName, $row);
        });
        $dump->start($this->getFullPath());
        echo "Database successfully exported at " . $this->getFullPath() . "\n";
    }

    /**
     * get an instance of Mysqldump.
     *
     * @return IMysqldump\Mysqldump
     * @throws \Exception
     */
    protected function getDumpInstance()
    {
        $database = DB::connection()->getConfig('database');
        $host = DB::connection()->getConfig('host');
        $username = DB::connection()->getConfig('username');
        $password = DB::connection()->getConfig('password');
        $dsn = 'mysql:host=' . $host . ';dbname=' . $database;

        return new IMysqldump\Mysqldump($dsn, $username, $password);
    }

    /**
     * Get full path to dump file.
     *
     * @return string
     */
    protected function getFullPath(): string
    {
        $path = storage_path('db-dumps');
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true, true);
        }

        $file = $this->argument('file') ?? date('Y-m-d_H-i-s');

        return $path . '/' . $file . '.sql';
    }

    /**
     * Check if the table column is a key.
     *
     * @param $tableName
     * @param $colName
     * 
     * @return bool
     */
    protected function isColumnKey($table, $colName): bool
    {
        if (!isset($this->keys[$table])) {
            $this->keys[$table] = DB::select(DB::raw('SHOW KEYS FROM ' . $table));
        }

        foreach ($this->keys[$table] as $key) {
            if ($key->Column_name == $colName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Anonymise a table column value.
     *
     * @param $tableName
     * @param $colName
     * @param $colValue
     * 
     * @return string
     */
    protected function anonymiseTableColumn($tableName, $colName, $colValue): string
    {
        if (isset($this->config[$tableName][$colName]['type'])) {
            $fakerType = $this->config[$tableName][$colName]['type'];
            return $this->faker->{$fakerType};
        }

        if (is_numeric($colValue)) {
            return $this->faker->regexify('[0-9]{' . strlen($colValue) . '}');
        }

        return $this->faker->regexify('[A-Za-z0-9]{' . strlen($colValue) . '}');
    }

    /**
     * Anonymise a table column row.
     *
     * @param $tableName
     * @param $row
     * 
     * @return array
     */
    protected function anonymiseTableRow($tableName, $row): array
    {
        foreach ($row as $colName => $colValue) {
            if (!$colValue) {
                continue;
            }

            if (isset($this->config[$tableName][$colName]['void']) && $this->config[$tableName][$colName]['void']) {
                $row[$colName] = null;
            } else {
                if (isset($this->config[$tableName][$colName]['anonymise'])) {
                    if ($this->config[$tableName][$colName]['anonymise']) {
                        $row[$colName] = $this->anonymiseTableColumn($tableName, $colName, $colValue);
                    }
                } else {
                    if (!$this->isColumnKey($tableName, $colName)) {
                        $row[$colName] = $this->anonymiseTableColumn($tableName, $colName, $colValue);
                    }
                }
            }
        }

        return $row;
    }
}
