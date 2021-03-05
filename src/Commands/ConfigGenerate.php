<?php

namespace torsal\AnonymisedSQLDumps\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class ConfigGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anonymised-db-dumps:generate-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a config file';

  
    protected $indentationSpaces = 4;

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
        $this->config = config('db-export');
        parent::__construct();
    }

    protected function getIndentation($tabs = 1)
    {
        return str_repeat(" ", $this->indentationSpaces * $tabs);
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
     * returns contents for new config file.
     *
     * @return mixed
     */
    protected function getConfigContents()
    {
        $contents = "<?php" . PHP_EOL ."return [". PHP_EOL; 

        $tables = array_map('reset', DB::getSchemaBuilder()->getAllTables());       
        foreach ($tables as $table){
            $contents .= $this->getIndentation() . "'" . $table . "' => [". PHP_EOL; 

            $columns = DB::getSchemaBuilder()->getColumnListing($table);       
            foreach ($columns as $column){
                $contents .= $this->getIndentation(2) . "'" . $column . "' => [";
                if (isset($this->config[$table][$column])) {

                    $configValue = null;
                    if (isset($this->config[$table][$column]['anonymise'])) {
                        $configValue .= "'anonymise' => " . ($this->config[$table][$column]['anonymise'] ? "true" : "false") . ",";
                    }

                    if (isset($this->config[$table][$column]['type'])) {
                        $configValue .= "'type' => '" . $this->config[$table][$column]['type'] . "',";
                    }

                    if (isset($this->config[$table][$column]['void'])) {
                        $configValue .= "'void' => " . ($this->config[$table][$column]['void'] ? "true" : "false") . ",";
                    }
                    
                    $contents .= rtrim($configValue, ',');

                } else {
                    $contents .= "'anonymise' => " . ($this->isColumnKey($table, $column) ? "false" : "true");
                }
                $contents .= "],". PHP_EOL;
            }

            $contents .= $this->getIndentation() . "],". PHP_EOL;
        }

        $contents .= "];". PHP_EOL;
        
        return $contents;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $path = App::configPath() . '/db-export.php';

        $handler = fopen($path, "wb");
        fwrite($handler, $this->getConfigContents());
        fclose($handler);
    }
}
