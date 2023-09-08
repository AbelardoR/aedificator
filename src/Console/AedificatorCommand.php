<?php
namespace Veainge\Aedificator\Console;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Support\Facades\File;
use PhpParser\Node\Expr\Cast\Array_;
use Spatie\LaravelIgnition\Recorders\DumpRecorder\Dump;

class AedificatorCommand extends Command
{
     /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Separator in directories variable
     *
     * var define
     */
    private $sep = DIRECTORY_SEPARATOR;
    private $blade = '.blade.php';
    private $php = '.php';
    private static $_tablePrefix = '';
    private $sufix = 'aed';

    public $tableName;
    public $folderName;
    public $modelName;
    public $modelNameS;
    public $ns; //Namespace for model
    public array $paths;
    public $lang = 'en';
    public $command_checker = null;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forge:table
                            {table_name? : Type the name of the table}
                            {--folder=Dashboard : Write the save folder(s)}
                            {--M|model : only create the model based on the table}
                            {--C|controller : only create controller based on table}
                            {--R|request : only create request based on table}
                            {--N|noviews : create a model, controller, request}
                            {--W|views : only create a views based on table}
                            {--L|lang=en : create files to be translated}
                            {--K|components : copy necessary components}
                            {--I|navigation : only adds one item to the navigation menu}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate files from tables based on their columns';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->init();
        $this->info('forge in progress from table '.$this->tableName);
        $this->buildDirectories();

        $this->CommandOptions();
        $this->info("forge of {$this->modelName} complete ");
    }
    public function init() {
        $this->tableName = $this->getTableName();
        $this->existTable($this->tableName);
        $this->modelName = $this->getPluralClassName($this->tableName);
        $this->modelNameS = $this->getSingularClassName($this->tableName);
        $this->folderName = $this->getInFolder();
        $this->ns = (!$this->folderName) ? $this->modelName : $this->folderName.$this->sep.$this->modelName ;
     }
    /**
     * Return the Name of table
     * @return mixed
     */
    protected function getTableName() {
        if (!$this->argument('table_name')) {
            $tableName = $this->ask('What is the name of the table?');
            if (!$tableName) { return $this->error('Table name required'); }
            return $tableName;
        } else {
            return $this->argument('table_name');
        }
    }

    /**
     * Check if table exist
     * @param $tableName
     * @return boolean
     */
    protected function existTable($tableName) {
        if (!Schema::hasTable($tableName)) {
            $this->error('table does not exist');
            exit;
        }
        return true;
    }

    /**
     * Check if want to only model or controller
     * @param $tableName
     * @return boolean
     */
    public function CommandOptions() {
        if ($this->option('model')) {
            $this->command_checker = 'model';
            $this->makeModel($this->paths['model']);
        }
        if ($this->option('controller')) {
            $this->command_checker = 'controller';
            $this->makeController($this->paths['controller']);
        }
        if ($this->option('request')) {
            $this->command_checker = 'request';
            $this->makeRequest($this->paths['request']);
        }
        if ($this->option('noviews')) {
            $this->command_checker = 'noviews';
            $this->makeModel($this->paths['model']);
            $this->makeController($this->paths['controller']);
            $this->makeRequest($this->paths['request']);
            $this->makeRoutes($this->paths['routes']);
        }
        if ($this->option('views')) {
            $this->command_checker = 'views';
            $this->makeMenu($this->paths['navigations']);
            $this->makeCrudViews($this->paths['view']);
        }
        if ($this->option('lang')) {
            $this->lang = $this->option('lang');
            if ($this->lang != 'en') {
                $this->command_checker = $this->option('lang');
                $this->paths['lang'] = $this->getFilePath('lang_path', $this->lang, 'models');
                $this->makeDirectory($this->paths['lang']);
            }
            $this->makeLocalize($this->paths['lang']);
        }
        if ($this->option('components')) {
            $this->command_checker = 'components';
            $this->copyComponents(true);
        }
        if ($this->option('navigation')) {
            $this->command_checker = 'navigation';
            $this->makeMenu($this->paths['navigations']);
        }
        if ($this->command_checker === null) {
            $this->makeModel($this->paths['model']);
            $this->makeController($this->paths['controller']);
            $this->makeRequest($this->paths['request']);
            $this->makeCrudViews($this->paths['view']);
            $this->makeMenu($this->paths['navigations']);
            $this->makeRoutes($this->paths['routes']);
            $this->copyComponents();
        }

    }

    /**
     * Return the Singular or Plural Capitalize Name
     * @param $tableName
     * @return string
     */
    public function getSingularClassName($tableName) {
        return Str::studly(Str::singular($tableName));
    }
    public function getPluralClassName($tableName) {
        return Str::studly(Str::plural($tableName));
    }


    /**
     * The function `getInFolder` returns a studly-cased folder name if provided as an option.
     *
     * @return string of folder name in studly case.
     */
    public function getInFolder() {
        $folderName = $this->option('folder');
        if ($folderName != '' ) {
            return Str::studly($folderName);
        }
    }

    /**
     * The function `getFilePath` takes in three parameters
     * and returns the path based on the values of these parameters.
     *
     * @param file_path The `file_path` parameter is a string that specifies the type of path to be
     * used. It can have two possible values: "app_path" or any other value.
     * @param folder The "folder" parameter is a string that represents the name of a folder. It is
     * used to construct the file path.
     * @param model_folder The `model_folder` parameter is a string that represents the name of a
     * folder where the model files are stored.
     *
     * @return string value of the variable $path .
     */
    protected function getFilePath($file_path, $folder, $model_folder) {
        if ($file_path == 'app_path') {
            $folder = Str::studly($folder);
            $model_folder = Str::studly($model_folder);
        } else {
            $folder = Str::lower($folder);
            $model_folder = Str::lower($model_folder);
        }
        $path = $file_path($folder.$this->sep.$model_folder);
        return $path;
    }



    /**
     * The function "buildDirectories" creates directories for different components of a PHP
     * application.
     */
    protected function buildDirectories() {
        $this->paths = [
            'model'      => $this->getFilePath('app_path', 'Models', $this->folderName),
            'controller' => $this->getFilePath('app_path', 'Http/Controllers', $this->folderName),
            'request'    => $this->getFilePath('app_path', 'Http/Requests', $this->folderName),
            'routes'     => $this->getFilePath('base_path', 'routes', null),
            'view'       => $this->getFilePath('resource_path', 'views', $this->ns),
            'components' => $this->getFilePath('resource_path', 'views', 'components/'.$this->sufix),
            'navigations' => $this->getFilePath('resource_path', 'views', 'navigations'),
            'lang'       => $this->getFilePath('lang_path', $this->lang, 'models'),
        ];
        $this->info('creating directiories');
        $bar = $this->output->createProgressBar(count($this->paths));
        $bar->start();
        foreach ($this->paths as $key => $path) {
            $this->makeDirectory($path);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
    }

    /**
     * The called `copyComponents()`. It is responsible for copying
     * necessary components from one this src to project components folder.
     */
    public function copyComponents($restore = false) {
        $des_path = resource_path('views/components/aed');
        $s_path = __DIR__.'/../components/aed';
        if ($restore && File::isDirectory($s_path)) {
            $replacing = $this->ask('Do you want to replace components ? yes(y) / not (n)', 'n');
            $replacing = Str::lower($replacing);
            if ($replacing == 'y' || $replacing == 'yes' ) {
                File::cleanDirectory($des_path);
            }
            $this->info('rewriting components');

        }
        if (File::isEmptyDirectory($des_path) && File::isWritable($des_path)) {
            if (File::isDirectory($s_path)) {
                $this->info('copying the necessary components');
                File::copyDirectory($s_path,  $des_path);
            }
        }
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param  string  $path
     * @return string
     */
    protected function makeDirectory($path) {
        if(!File::isDirectory($path) && !File::exists($path)){
            File::makeDirectory($path, 0777, true, true);
        }
        return $path;
    }

    /**
     * Build the view files required for resource route.
     * CRUD VIEWS
     *
     * @param  string  $path
     * @return mixed
     */
    public function makeCrudViews($path) {

        $crud = ['index', 'create', 'edit', 'fields', 'table', 'show', 'show_fields'];
        $folder = 'crud';
        $this->info('creating views');
        $bar = $this->output->createProgressBar(count($this->paths));
        $bar->start();
        foreach ($crud as $viewId) {
            $params = $this->getSourceFile($viewId, $folder);
            $newFile = $this->getSourceFilePath($path, $viewId, 'blade');
            if (!File::exists($newFile)) {
                File::put($newFile, $params);
            } else {
                $this->newLine();
                $this->warn("View File: {$viewId} already exits");
                $this->replacement([$newFile, $params]);
            }
            $this->line('adding content...');
            $this->fillView($viewId, $newFile);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
    }

    /**
     * The function `fillView` takes a view ID and a new file as parameters, and based on the view ID,
     * it calls different methods to build and fill the view with fields, show fields, or a table.
     *
     * @param viewId The `viewId` parameter is a string that represents the type of view to be filled.
     * It can have one of the following values: 'show_fields', 'fields', or 'table'.
     * @param newFile The  parameter is the file path where the generated view will be saved.
     */
    public function fillView($viewId, $newFile) {
        if ($viewId == 'show_fields') {
            $this->buildShowFields($this->getFieldsOfTable(), $newFile);
        }
        if ($viewId == 'fields') {
            $this->buildFields($this->getFieldsOfTable(), $newFile);
        }
        if ($viewId == 'table') {
            $this->buildTable($this->getFieldsOfTable(), $newFile);
        }
    }

    /**
     * The makeMenu function creates or appends content to a menu file based on the provided path.
     *
     * @param path The `path` parameter is a string representing the directory path where the menu file
     * will be created or updated.
     */
    private function makeMenu($path) {
        $file = 'menu';
        $params = $this->getSourceFile($file, 'crud');
        $result = $path.$this->sep.$file.$this->blade;
        if (!File::exists($this->getSourceFilePath($path, $file, 'blade'))) {
            File::put($result, $params);
            $this->info("File : {$file} created");
        } else {
            $this->warn("File : {$file} already exits");
            $this->line("appending additional content");
            File::append($result, $params);
            $this->info("File : {$file} updated");
        }
    }

    /**
     * Build the resource routes for model.
     * CRUD ROUTES
     *
     * @param  string  $path
     * @return void
     */
    private function makeRoutes($path) {
        $file = 'crudroutes';
        $params = $this->getSourceFile($file, 'crud');
        $result = $path.$this->sep.$file.$this->php;
        if (!File::exists($this->getSourceFilePath($path, $file, 'php'))) {
            File::put($result, $params);
            File::prepend($result, '<?php'."\r\n"."use Illuminate\Support\Facades\Route;"."\r\n");
            File::append(base_path('routes/web.php'), "\r\n"."require __DIR__.'/crudroutes.php';");
            $this->info("File : {$file} created");
        } else {
            $this->warn("File : {$file} already exits");
            $this->line("appending additional content");
            File::append($result, $params);
        }
    }

    /**
     * Build the resource routes for model.
     * MODELS
     *
     * @param  string  $path
     * @return void
     */
    private function makeModel($path) {
       $params = $this->getSourceFile('model', 'model');
       $result = $this->getSourceFilePath($path, $this->modelNameS, 'php');
       if (!File::exists($result)) {
            File::put($result, $params);
            $this->info("File : {$this->modelNameS} model created");
       } else {
            $this->warn("File : {$this->modelNameS} model already exits");
            $replace = $this->replacement([$result, $params]);
       }
       $this->buildModelElem($this->getFieldsOfTable(), $result);
    }

    /**
     * Build the resource routes for model.
     * CONTROLLERS
     *
     * @param  string  $path
     * @return void
     */
    private function makeController($path) {
        $file = $this->modelNameS.'Controller';
        $params = $this->getSourceFile('controller', 'controller');
        $pathFile = $this->getSourceFilePath($path, $file, 'php');
        if (!File::exists($pathFile)) {
            File::put($pathFile, $params);
            $this->info("File : {$file} created");
        } else {
            $this->warn("File : {$file} already exits");
            $this->replacement([$pathFile, $params]);
        }
    }

    /**
     * Build the view files required for resource route.
     * REQUESTS
     *
     * @param  string  $path
     * @return mixed
     */
    public function makeRequest($path) {
        $requests = ['store','update'];

        foreach ($requests as $file) {
            $file = Str::studly($file).$this->modelNameS.'Request';
            $params = $this->getSourceFile('request', 'request', $file); //geting file templated by stub
            $pathFile = $this->getSourceFilePath($path, $file, 'php');
            if (!File::exists($pathFile)) {
                File::put($pathFile, $params);
                $this->info("File : {$file} created");
            } else {
                $this->warn("File : {$file} already exits");
                $this->replacement([$pathFile, $params]);
            }
        }

    }

    /**
     * Build the localization of model.
     * LOCALIZATION
     *
     * @param  string  $path
     * @return mixed
     */
    private function makeLocalize($path) {
        $file = Str::lower($this->modelName);
        $params = $this->getSourceFile('lang', 'model');
        $pathFile = $this->getSourceFilePath($path, $file, 'php');
        if (!File::exists($pathFile)) {
            File::put($pathFile, $params);
            $this->info("File : {$pathFile} created");
        } else {
            $this->warn("File : {$pathFile} already exits");
            $this->replacement([$pathFile, $params]);
        }
        $this->buildLocalizeModel($this->getFieldsOfTable(), $pathFile );
    }

    public function replacement(array $fileReplace) {
        $r=0;
        $replacing = $this->ask('Do you want to replace it ? yes(y) / not (n)', 'n');
        $replacing = Str::lower($replacing);
        if ($replacing == 'y' || $replacing == 'yes' ) {
            File::replace($fileReplace[0], $fileReplace[1]);
            $this->info("File : {$fileReplace[0]} \nrefurbished");
            $r=1;
        }
        return $r;
    }

    /**
     * Get the full path of file
     *
     * @return string
     */
    public function getSourceFilePath($path, $file, $ext)
    {
        if ($ext == 'blade') {
            return $path.'\\'.$file.$this->blade;
        } else {
            return $path.$this->sep.$file.'.php';
        }
    }

    /**
     * Return the stub file path
     * @return string
     *
     */
    public function getStubPath($file, $folder)
    {
        //with variable $file
        $bp = sprintf(__DIR__.'/../../stubs/%2$s/%1$s.stub', $file, $folder);
        return $bp;
    }

    /**
     * Get the stub path and the stub variables
     * For files
     * @return bool|mixed|string
     *
     */
    public function getSourceFile($file, $folder, $var=null) {
        //add variable $file to get dynamic template stub
        return $this->getStubContents($this->getStubPath($file, $folder), $this->getStubVariables($file, $var));
    }

    /**
     * Get the stub path and the stub variables
     * For fields
     * @return bool|mixed|string
     *
     */
    public function getSourceField($field, $input, $length)
    {
        //add variable $file to get dynamic template stub
        return $this->getStubContents($this->getStubPath($field, 'field'), $this->getStubFieldsVar($input, $length));
    }

    /**
     * Replace the stub variables(key) with the desire value
     *
     * @param $stub
     * @param array $stubVariables
     * @return bool|mixed|string
     */
    public function getStubContents($stub , $stubVariables = []) {
        if (!File::exists($stub)) {
            $this->error('the template(stub) does not exist.');
            $this->info('An empty file will be created');
            return false;
        }
        $contents = file_get_contents($stub);

        foreach ($stubVariables as $search => $replace)
        {
            $contents = str_replace('$'.$search.'$' , $replace, $contents);
        }

        return $contents;

    }

    /**
     * Get the description fields of table
     *
     * @return array
     */
    private function getFieldsOfTable() {
        $tableColunms = [];
        $tableColunms = DB::select('describe '.$this->getTableName());
        $tableColunms = \collect(DB::select('describe '.$this->getTableName()));
        $keyed = $tableColunms->mapWithKeys(function ($item, $key) {
            $newKey = $item->Field;
            $contains = Str::contains($item->Type, ['(', ')']);
            if ($contains) { $lim = Str::between($item->Type, '(', ')');} else { $lim = ''; }
            return [$newKey => [$item->Type, $lim, $item->Null]];
        });

        $filtered = Arr::except($keyed, ['id']);//excluded id key
        return $filtered;
    }

    /**
     * Get the type of columns of table
     *
     * @return $value
     */
    private function getColumnType($columnType) {
        switch (true) {
            case Str::contains($columnType, 'int'):
                $column = 'integer';
                break;
            case Str::contains($columnType, 'date'):
            case Str::contains($columnType, 'time'):
                $column ='date';
                break;
            case Str::contains($columnType, 'boolean'):
                $column ='boolean';
                break;
            default:
                $column ='string';
                break;
        }
        return $column;
    }

    /**
     * Determines the type of field that will be used
     * according to the data in the table
     * @return $value
     */
    private function getFieldsType($fieldType) {
        switch (true) {
            case Str::contains($fieldType, 'int'):
                $field ='number';
                break;
            case Str::contains($fieldType, 'enu'):
                $field ='select';
                break;
            case Str::contains($fieldType, 'text'):
                $field ='textarea';
                break;
            case Str::contains($fieldType, 'date'):
            case Str::contains($fieldType, 'time'):
                $field ='date';
                break;
            case Str::contains($fieldType, 'boolean'):
                $field ='checkbox';
                break;
            default:
                $field ='text';
                break;
        }
        return $field;
    }


    /**
     * The function builds a table using an array of fields and replaces placeholders in a file with
     * the table header and body.
     *
     * @param arrayFields An array containing the fields and their values.
     * @param file The `file` parameter is the path to the file that you want to modify. It should be a
     * valid file path on your server.
     *
     * @return boolean value. If the file does not exist, it returns false. Otherwise, it returns
     * true.
     */
    private function buildTable($arrayFields, $file) {
        if (!File::exists($file)) {
            $this->error('the file does not exist.');
            $this->info('Please create it first');
            return false;
        }
        $contents = file_get_contents($file);
        $br = "\r\n";
        $tableHeader ='';
        foreach($arrayFields as $key => $value){
            $field = htmlspecialchars($key);
            $tableHeader .= "<th>{!! __('models/".$this->modelName.".fields.".$field."') !!} </th>".$br;
        }
        $tableHeader .='';
        $contents = str_replace('$FIELDS_HEADERS$', $tableHeader, $contents);

        $tableBody ='';
        foreach($arrayFields as $key => $value){
            $field = htmlspecialchars($key);
            $tableBody .= '<td>{!! $item->'.$field. ' !!} </td>'.$br;
        }
        $tableBody .='';
        $contents = str_replace('$FIELDS_BODY$' ,  $tableBody, $contents);
        // return ['th' => $tableHeader , 'td' => $tableBody] ;
        File::replace($file, $contents);
    }


    /**
     * The function "buildFields" takes an array of fields and a file path as parameters, and appends
     * the source code for each field to the specified file.
     *
     * @param arrayFields An array containing the fields and their corresponding values. Each element
     * in the array should be in the format:
     * @param pathFile The `pathFile` parameter is the path to the file where the fields will be
     * appended.
     */
    private function buildFields($arrayFields, $pathFile) {
        foreach ($arrayFields as $input => $value) {
            $field =  $this->getFieldsType($value[0]);
            File::append($pathFile, $this->getSourceField($field, $input, $value[1]));
        }

    }

    /**
     * The function "buildShowFields" appends the source field for each input in the given array to the
     * specified file path.
     *
     * @param arrayFields An array containing the fields to be shown and their corresponding values.
     * Each element in the array should have the field name as the key and an array as the value. The
     * array should contain two elements: the input type and the value of the field.
     * @param pathFile The `pathFile` parameter is the path to the file where the generated code will
     * be appended.
     */
    private function buildShowFields($arrayFields, $pathFile) {
        foreach ($arrayFields as $input => $value) {
            File::append($pathFile, $this->getSourceField('show', $input, $value[1]));
        }
    }

    /**
     * The function builds a model element by replacing placeholders in a file with values from an
     * array.
     *
     * @param arrayFields An array containing the fields of the model. Each field is represented by a
     * key-value pair, where the key is the field name and the value is an array containing the field
     * type, field length, and field constraint.
     * @param file The file parameter is the path to the file that needs to be modified.
     *
     * @return a boolean value. If the file does not exist, it returns false. Otherwise, it returns
     * true.
     */
    private function buildModelElem($arrayFields, $file) {

        if (!File::exists($file)) {
            $this->error('the file does not exist.');
            $this->info('Please create it first');
            return false;
        }

        $contents = file_get_contents($file);
        $br = "\r\n";
        $fillable = $arrayFields->keys();
        $contents = str_replace('$MODEL_COLUMNS_KEYS$', ($fillable), $contents);
        $casts = "[".$br;
        $casts .= "'id' => 'integer',".$br;
        foreach($arrayFields as $key => $value){
            $field = htmlspecialchars($key);
            $casts .= "'".$field."' => '".$this->getColumnType($value[0])."',".$br;
        }
        $casts .= "]";
        $contents = str_replace('$MODEL_COLUMS_CAST$', $casts, $contents);

        $rules = "[".$br;
        foreach($arrayFields as $key => $value){
            $field = htmlspecialchars($key);
            $req = ($value[2] != 'YES') ? 'required' : 'nullable' ;
            $max = (!empty($value[1])) ? $value[1] : '255';
            $rules.= sprintf("'%s' => '%s|%s|max:%u',", $field, $req, $this->getColumnType($value[0]), $max).$br;
        }
        $rules .= "]";

        $contents = str_replace('$MODEL_COLUMS_RULES$', $rules, $contents);
        File::replace($file, $contents);

    }

    /**
     * The function builds a localized model by adding content to a specified file.
     *
     * @param arrayFields An array containing the fields that need to be added to the localize model.
     * Each key-value pair represents the field name and its corresponding value.
     * @param file The "file" parameter is the path to the file where the content will be added.
     *
     * @return boolean value. If the file does not exist, it returns false. Otherwise, it does not
     * explicitly return anything, so it will return null.
     */
    private function buildLocalizeModel($arrayFields, $file) {
        if (!File::exists($file)) {
            $this->error('the file does not exist.');
            $this->info('Please create it first');
            return false;
        }
        $contents = file_get_contents($file);
        $this->info("File : adding content to {$file}");
        $br = "\r\n";
        $fields = "[".$br;
        $fields .= "'id' => 'id',".$br;
        foreach($arrayFields as $key => $value){
            $field = htmlspecialchars($key);
            $fields .= "'".$field."' => '".$field."',".$br;
        }
        $fields .= "]";
        $contents = str_replace('$FIELDS_ARRAY$', $fields, $contents);
        File::replace($file, $contents);
    }

    /**
     * The function `getStubVariables` returns an array of variables used for code generation.
     *
     * @param file The file parameter is the name or path of the file that the function is being called
     * from. It is optional and can be set to null if not needed.
     * @param var The variable `` is an optional parameter that can be passed to the
     * `getStubVariables` function. It is not used within the function and its purpose is not clear
     * from the provided code.
     *
     * @return array of variables.
     */
    public function getStubVariables($file, $var=null) {

        $params =  [
            'FILES'                          => '',
            'FIELDS'                         => '',
            'FOLDER_LOWER'                   => Str::lower($this->folderName),
            'FOLDER_STUDLY'                  => Str::studly($this->folderName),
            'MODEL_QUERY'                    => 'craft',
            'STORE_STRING'                   => 'Store',
            'UPDATE_STRING'                  => 'Update',
            'TABLE_NAME_LOWER'               => $this->tableName,
            'ROUTE_NAMED_PREFIX'             => '',
            'MODEL_NAME_LOWER_PLURAL'        => Str::lower($this->modelName),
            'MODEL_NAME_STUDLY_PLURAL'       => Str::studly($this->modelName),
            'MODEL_NAME_LOWER_SINGULAR'      => Str::lower($this->modelNameS),
            'MODEL_NAME_STUDLY_SINGULAR'     => Str::studly($this->modelNameS),
            'CONTROLLER_NAME_LOWER_SINGULAR' => Str::lower($this->modelNameS.'Controller'),
            'CONTROLLER_NAME_STUDLY_SINGULAR'=> Str::studly($this->modelNameS.'Controller'),
            'REQUEST_NAME_STUDLY_SINGULAR'   => Str::studly($this->modelNameS.'Request'),
            'REQUEST_PREFIX_STUDLY_SINGULAR' => Str::studly($var),
            'VIEW_FOLDER_LOWER'              => Str::lower($this->folderName),
            'PATH_PREFIX_UCFIRST'            => Str::ucfirst($this->folderName),
            'MODEL_SPACENAME'                => 'App\Models',
            'CONTROLLER_SPACENAME'           => 'App\Http\Controllers',
            'REQUEST_SPACENAME'              => 'App\Http\Requests',
            'DATE_SCAFFOLD'                  => now(),
        ];
        return $params;
    }

   /**
    * The function `getStubFieldsVar` returns an array of parameters with values based on the input and
    * other variables.
    *
    * @param input The input parameter is a string that represents the field name.
    * @param length The "length" parameter is a string that represents the maximum length of the field.
    * It has a default value of '255', but you can pass a different value if needed.
    *
    * @return an array called .
    */
    public function getStubFieldsVar($input, $length='255') {
        $params = [
            'FIELD_NAME_TITLE'          => Str::title($input),
            'FIELD_NAME_LOWER'          => Str::lower($input),
            'MODEL_NAME_LOWER_PLURAL'   => Str::lower($this->modelName),
            'MODEL_QUERY'               => 'craft',
            'SIZE'                      => $length,
            'CHECKBOX_VALUE'            => '1',
            'INPUT_ARRAY'               => '',
        ];
        return $params;
    }







## end of class
}
