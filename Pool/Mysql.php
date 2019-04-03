<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-4-1
 * Time: 上午9:42
 */

namespace Pool;
use Swoole\Coroutine\MySQL as CoMysql;
use Helper\Log;
/** 这里的mysql orm 采用的是基于swoole协程的mysql客户端 */

/**
 * 由于这个在一个进程中需要实例化多个客户端链接　　这里无法使用单例模式
 * Class Mysql
 * @package Pool
 * @author chenlin
 * @date 2019/4/1
 */
class Mysql extends  CoMysql{

    /** @var string 用于关联操作的sql拼接操作 */
    protected  $sql;
    //设置查询的字段
    protected  $fields = '*';
    //设置表名
    protected  $table;
    //设置排序
    protected  $orders;
    //条件查询
    protected  $wheres;
    //分组设置
    protected  $groups;
    /**
     * 构造函数
     * Mysql constructor.
     * @param $type
     */
    public function __construct(){
        parent::__construct();
    }

    /**
     * 数据库连接信息
     * @param array $serverInfo
     * @return bool
     * @author chenlin
     * @date 2019/4/1
     */
    public function connect(array $serverInfo){
        return parent::connect($serverInfo); // TODO: Change the autogenerated stub
    }

    /** 这下就是关联操作 */

    /**
     * 执行查询操作
     * @param string $sql 查询的sql语句
     * @param float $timeout 查询的超时时间  errorcode
     * @return mixed
     * @author chenlin
     * @date 2019/4/1
     */
    public function query($sql, $timeout = 0.0){
        //对执行的SQL语句进行记录
        Log::getInstance()->record("[TIME:".date('Y-m-d H:i:s',time())." SQL Record]:{$sql}".PHP_EOL);
        return parent::query($sql, $timeout); // TODO: Change the autogenerated stub
    }

    /**
     * 执行查询　
     * @param $timeout  执行查询的超时时间
     * @return mixed
     * @author chenlin
     * @date 2019/4/1
     */
    public function select(float $timeout = 0.0){
        //开始整理sql语句
        $this->sql = "SELECT {$this->fields} FROM {$this->table}";
        //有条件的拼接
        if(!empty($this->wheres)){
            $this->sql .= " WHERE {$this->wheres}";
        }
        if(!empty($this->groups)){
            $this->sql .= " GROUP BY {$this->groups}";
        }
        if(!empty($this->orders)){
            $this->sql .= " ORDER BY {$this->orders}";
        }
        return $this->query($this->sql,$timeout);
    }

    /**
     * 查询一条记录
     * @param float $timeout
     * @return mixed
     * @author chenlin
     * @date 2019/4/1
     */
    public function first(float $timeout = 0.0){
        //开始整理sql语句
        $this->sql = "SELECT {$this->fields} FROM {$this->table}";
        //有条件的拼接
        if(!empty($this->wheres)){
            $this->sql .= " WHERE {$this->wheres}";
        }
        if(!empty($this->groups)){
            $this->sql .= " GROUP BY {$this->groups}";
        }
        if(!empty($this->orders)){
            $this->sql .= " ORDER BY {$this->orders}";
        }
        $this->sql .= " LIMIT 1";
        return $this->query($this->sql,$timeout);
    }
    /**
     * 设置查询的表
     * @param string $table_name
     * @return bool
     * @author chenlin
     * @date 2019/4/1
     */
    public function setTable(string $table_name){
        $this->table = $table_name;
        $this->sql = '';
        return $this;
    }

    /**
     * 设置数据库查询的字段的设置
     * @param $fields string|array 如果是array则是一维下标数组 如果是string 则是用,隔开的字符串
     * @return Mysql对象
     * @author chenlin
     * @date 2019/4/1
     */
    public function field($fields){
        if(is_array($fields)){
            $fields = implode(',',$fields);
        }
       $this->fields = $fields;
        return $this;
    }


    /**
     * 查询的条件　
     * @tip 这里做了一个限制　　一个key只能对应一个条件限制 不然直接忽略掉设置的条件
     * @param $where array
     */
    public function where($where){
        if(is_callable($where)){
            $where($this);
        }else if(is_array($where)){
            $this->wheres .= '(';
            foreach($where as $key => $value){
                if(is_int($key)){
                    if(is_array($value) && count($value) == 3){
                        $this->wheres .= " {$value[0]} {$value[1]}";
                        is_string($value[2]) ? $this->wheres.=" '{$value[2]}' AND" : $this->wheres.=" {$value[2]} AND";
                    }
                }else if(is_string($key)){
                    if(is_int($value)){
                        $this->wheres .= " {$key} = {$value} AND";
                    }else if(is_string($value)){
                        $this->wheres .= " {$key} = '{$value}' AND";
                    }
                }
            }
            //去掉最后一个AND
            $this->wheres = mb_substr($this->wheres,0,-3);
            $this->wheres .= ')';
        }
        return $this;
    }

    /**
     * 设置排序字段
     * @param array
     * @author chenlin
     * @date 2019/4/1
     */
    public function order(array $order){
        //这个order排序不能设置多次
        $this->orders = ''; //防止多次调用　导致出现问题　这里每次调用的时候　都进行置空操作
        foreach($order as $key => $value){
            $this->orders .= "{$key} {$value},";
        }

        //去掉最后一个逗号
        $this->orders = mb_substr($this->orders,0,-1);
        return $this;
    }


    /**
     * 分组设置
     * @param array $group
     * @return Mysql
     * @author chenlin
     * @date 2019/4/1
     */
    public function group($group){
        //这个mysql目前也就只能设置一次'
        $this->groups = ''; //防止多次调用　导致出现问题　这里每次调用的时候　都进行置空操作
        if(is_string($group)){
            $this->groups .= $group;
        }

        //如果是数组的话　还要进行判断
        if(is_array($group) && count($group) == 1){
            $this->groups .= $group[0];
        }else if(is_array($group) && count($group) > 1){
            $this->groups .= implode(',',$group);
        }
        return $this;
    }

    /**
     * 重置mysql连接资源的一些属性信息重置
     * @tip 防止下一次
     */
    public function reset(){
        $this->sql = '';
        $this->orders = '';
        $this->groups = '';
        $this->wheres = '';
        $this->fields = '';
        $this->table  = '';
        return true;
    }
}