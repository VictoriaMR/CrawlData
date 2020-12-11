<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * 数据库配置
 */
class Schema
{
    public $oCapsule;
    public $aCapsule;

    /**
     * 创建数据库初始配置
     */
    public function __construct($aCapsule)
    {
        // 加载本地配置文件
        $this->aCapsule = $aCapsule;

        // 创建链接
        $this->oCapsule = new Capsule;
        // 设置数据库链接 确认链接在
        $this->oCapsule->addConnection($this->aCapsule['master'], 'master');

        // 设置数据库事件
        $this->oCapsule->setEventDispatcher(new Illuminate\Events\Dispatcher(new Illuminate\Container\Container));

        // 设置全局静态可访问
        $this->oCapsule->setAsGlobal();
        // 启动Eloquent
        $this->oCapsule->bootEloquent();
    }

    /**
     * 创建开发数据库
     */
    public function createDatabase()
    {
    	$aTable = [];
        $aQuery = Capsule::connection('master')->select('show databases');
        foreach ($aQuery as $oTable)
        {
            $aTable[$oTable->Database] = $oTable->Database;
        }
        
        $createDatabase = false;
        // 创建开发服务器数据库
        if (!isset($aTable[$this->aCapsule['default']['database']]))
        {
            $sQuery = 'create database `%s` character set utf8 collate utf8_unicode_ci';
            $sQuery = sprintf($sQuery, $this->aCapsule['default']['database']);
            echo sprintf('create database %s !!',$this->aCapsule['default']['database']).PHP_EOL;
            Capsule::connection('master')->statement($sQuery);
            $createDatabase = true;
        }
        // 添加开发服务器数据库
        $this->oCapsule->addConnection($this->aCapsule['default']);

    	if ($createDatabase)
		{
			echo 'start check database is ok !!!'.PHP_EOL;
		}
        
        $this->createTables();
    }

    /**
     * 创建数据表
     */
    protected function createTables()
    {
        //添加数据表
        $sTable = 'demo_table';
        if (!Capsule::schema()->hasTable($sTable))
        {
            Capsule::schema()->create($sTable, function (Blueprint $oTable) {
                //自增id
                $oTable->increments('id');
                //char
                $oTable->char('name', 255)->default('');
                //varchar
                $oTable->string('info',255)->default('')->index();
                //int
                $oTable->integer('number')->default(0)->index();
            });
            echo $sTable." has created!".PHP_EOL;
        }

        echo "create Table ok !!".PHP_EOL;
        return true;
    }
}