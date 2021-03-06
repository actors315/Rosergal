<?php
/**
 *
 * 应用容器
 *
 * */


namespace Twinkle\Library\Framework;

use Exception;
use ReflectionClass;
use Twinkle\Database\Connection;
use Twinkle\Library\Common\Request;
use Twinkle\Library\Common\Response;
use Twinkle\Library\Config\ConfigLoader;
use Twinkle\Library\Service\MasterDbServiceProvider;
use Twinkle\Library\Service\SlaveDbServiceProvider;


class Container extends \Twinkle\DI\Container
{
    /**
     *
     * 存放可以重复利用的实例
     * */
    public $mapperInstances;


    private static $instance = null;


    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     *
     * 简短名对类名的映射
     * */
    public $aliasMapperClass = [

    ];


    /**
     *
     * 初始化注入系统服务
     *
     * @return void
     * */
    public function initializationService()
    {

    }


    /**
     *
     * 初始化注入系统组件
     *
     * @return void
     * */
    public function initializationComponent()
    {
        $this->injection('request', Request::getInstance());
        $this->injection('response', Response::getInstance());
    }


    /**
     *
     * 初始化注入系统插件
     *
     * @return void
     */
    public function initializationPlugin()
    {

        Hook::getInstance()->registerPlugin('router', new \Twinkle\Library\Plugin\RouterPlugin());
        Hook::getInstance()->registerPlugin('log', new \Twinkle\Library\Plugin\LogPlugin());
    }
    
    
    /**
     * 注入系统配置
     * 
     */
    public function loadSystemConfig()
    {
        #加载系统级配置

    }


    /**
     *
     * 注入组件
     * @param string $name 简短名
     * @param mixed $concrete 字符串，闭包，对象
     * @throws \Exception
     * @return bool
     * */
    public function injection($name, $concrete)
    {
        //注入服务提供器
        if (is_string($name) && strtolower(substr($name, -7)) == 'service') {
            if ($concrete instanceof \Closure) {
                $this->mapperInstances[$name] = $concrete;
            } else {
                throw new \Exception($name . ':注入服务器必须是闭包', 10000);
            }
            return true;
        }


        if (is_string($name)) {
            parent::injection($name,$concrete);
            return true;
        }

        throw new \Exception($name . ':注入组件', 10000);

    }


    /**
     *
     * 生成对象
     *
     * @param string $name must
     * @param array $parameters 构造函数的参数
     * @throws \Exception
     * @return object
     * */
    public function make($name, array $parameters = [])
    {
        //服务类需要先注入,而且必须是闭包
        if (is_string($name) && strtolower(substr($name, -7)) == 'service') {

            if (!isset($this->mapperInstances[$name])) {
                throw new Exception('服务提供器需要提前注入', 10000);
            }
            $concrete = $this->mapperInstances[$name];
            if ($concrete instanceof \Closure) {
                return $concrete();
            }
            throw new Exception('服务提供器生成异常', 10000);

        }

        //其他组件需要提前配置映射对应的简短名和类路径
        if (is_string($name)) {
            #参数只能是简短名称,不能是全路径名空间
            if (strpos($name, '\\') !== false) {
                throw new \Exception('参数只能是简短名称,不能是全路径名空间', 10000);
            }

            if (isset($this->definitions[$name])) {
                $concrete = $this->definitions[$name];
                return parent::reflector($concrete,$parameters);
            }

            //如果之前生成过，就直接返回
            if (isset($this->mapperInstances[$name])) {
                return $this->mapperInstances[$name];
            }

            $concrete = isset($this->aliasMapperClass[$name]) ? $this->aliasMapperClass[$name] : null;

            if (is_null($concrete)) {
                throw new \Exception('组件需要提前配置映射对应的简短名和类路径', 10000);
            }

            //反射生成对象
            $object = $this->reflector($concrete, $parameters);

            $this->mapperInstances[$name] = $object;

            return $object;
        }

        throw new Exception('make 参数异常', 10000);
    }

    /**
     * 生成对象
     *
     * @param string $concrete 类名
     * @param array $parameters 构造类的参数
     * @return object
     * @throws Exception
     */
    public function reflector($concrete, array $parameters = [])
    {
        if (is_string($concrete)) {
            $reflector = new ReflectionClass($concrete);

            if (!$reflector->isInstantiable()) {
                throw new \Exception($concrete . ':该类不可实例化', 10000);
            }

            $constructor = $reflector->getConstructor();

            if (is_null($constructor)) {
                throw new \Exception($concrete . ':构造函数出现错误', 10000);
            }

            //有传参数构造的话就用用户的实参
            if (!empty($parameters)) {
                $object = $reflector->newInstanceArgs($parameters);
                return $object;
            }
            //没有传入就用默认参数
            $dependencies = $constructor->getParameters();

            $parameters = $this->getParametersByDependencies($dependencies);

            if (empty($parameters)) {
                $object = $reflector->newInstance();
            } else {
                $object = $reflector->newInstanceArgs($parameters);
            }
            return $object;
        }
    }
    
    
    public function reflectorDebug($concrete, array $parameters = [])
    {
        
        if (is_string($concrete)) {
            $reflector = new ReflectionClass($concrete);
            
            if (!$reflector->isInstantiable()) {
                throw new \Exception($concrete . ':该类不可实例化', 10000);
            }
            
            $constructor = $reflector->getConstructor();
            
            if (is_null($constructor)) {
                throw new \Exception($concrete . ':构造函数出现错误', 10000);
            }
            
            //有传参数构造的话就用用户的实参
            if (!empty($parameters)) {
                $object = $reflector->newInstanceArgs($parameters);
                
                return $object;
            }
            //没有传入就用默认参数
            $dependencies = $constructor->getParameters();
            
            $parameters = $this->getParametersByDependencies($dependencies);
            
            if (empty($parameters)) {
                $object = $reflector->newInstance();
            } else {
                $object = $reflector->newInstanceArgs($parameters);
            }
            return $object;
        }
    }


    /**
     *
     * 获取构造类相关参数的依赖
     * @param array $dependencies
     * @return array $parameters
     * */
    public function getParametersByDependencies(array $dependencies)
    {
        $parameters = [];

        foreach ($dependencies as $param) {
            if ($param->getClass()) {

                $paramName = $param->getClass()->name;
                if ($paramName === 'Twinkle\Library\Framework\Container') {
                    $parameters[] = static::$instance;
                } else {
                    $paramObject = $this->reflector($paramName);
                    $parameters[] = $paramObject;
                }

                //对象参数的话 反射获取


            } elseif ($param->isArray()) {

                if ($param->isDefaultValueAvailable()) {
                    $parameters[] = $param->getDefaultValue();
                } else {
                    $parameters[] = [];
                }
            } elseif ($param->isCallable()) {
                if ($param->isDefaultValueAvailable()) {
                    $parameters[] = $param->getDefaultValue();
                } else {
                    $parameters[] = function ($sss) {
                    };
                }
            } else {
                if ($param->isDefaultValueAvailable()) {
                    $parameters[] = $param->getDefaultValue();
                } else {
                    if ($param->allowsNull()) {
                        $parameters[] = null;
                    } else {
                        $parameters[] = false;
                    }
                }
            }
        }
        return $parameters;
    }


    public function __toString()
    {
        pre($this->aliasMapperClass);
        pre($this->mapperInstances);
        return 'f';
    }
}
