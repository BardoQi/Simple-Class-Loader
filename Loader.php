<?php

/**
 * This file is part of Bulo framework.
 *
 * Bulo -- An open source framework for Php 5.4.0 or newer.
 *
 * @author     Bardo QI
 * @link       http://www.Bulo.org
 * @license    http://www.Bulo.org/license.html
 * @version    1.0
 * @package    Bulo
 * @filesource
 */

/**
 * Loader: a loader for manual loading dependency class.
 * With this class, we could replace some requirement of AOP or DI.
 * With using Loader, we could use unit testing.
 */

namespace Bulo\Core\Loader
{

    /**
     * Loader
     * 
     * @package        Bulo
     * @subpackage    Core
     * @category    Loader
     * @author        Bulo Dev Team: Bardo QI
     * @link        
     */
    class Loader
    {
        /**
         * @property $objects
         * @type mixed
         * @access public static
         * @desc A array to storing the preloaded classes.
         */
        public static $objects = array();

        /**
         * @property $configs
         * @type mixed
         * @access public static
         * @desc A array of config.
         */
        public static $configs = array();

        /**
         * Doci::load()
         * 
         * @param mixed $className: must be full namespace classname
         * @param mixed $params
         * @param mixed $instanceName
         * @param mixed $shared
         * @return
         */
        public static function load( $className, $params = null, $instanceName = null,
            $shared = true, $initFunction = null )
        {
            $instanceName = ( $instanceName == null ) ? $className : $instanceName;
            if ( isset( self::$objects[$instanceName] ) && ( $shared == true ) )
                return self::$objects[$instanceName];
            if ( $params == null )
            {
                if ( isset( self::$configs[$instanceName] ) )
                    extract( self::$configs[$instanceName], EXTR_OVERWRITE );
            }
            $dociRet = self::createObject( $className, $params, $initFunction );
            if ( ( $shared === true ) && ( is_object( $dociRet ) ) )
                self::$objects[$instanceName] = $dociRet;
            return $dociRet;
        }

        /**
         * Loader::add()
         * For adding the Object cerated already. 
         * @param mixed $instanceName
         * @param mixed $object
         * @return
         */
        public static function add( $instanceName, $object )
        {
            self::$objects[$instanceName] = $object;
            return true;
        }

        /**
         * Loader::addConfigs()
         * $configs=array('instanceName'=>array(
         *                          'className'=>$className //full namespace classname1
         *                          'params'=>$params,
         *                          'instanceName'=>$instanceName,
         *                          'shared'=>$shared,
         *                          'initFunction'=>$initFunction
         *                              )
         *                'instanceName'=>array(
         *                          'className'=>$className //full namespace classname2
         *                          'params'=>$params,
         *                          'instanceName'=>$instanceName,
         *                          'shared'=>$shared,
         *                          'initFunction'=>$initFunction
         *                              ) )
         * $initFunction must be the static member function name or a closure. 
         * For example: I18n::init, the function name is init.
         * @param mixed $configs 
         * @return
         */
        public static function addConfigs( $configs )
        {
            if ( ! is_array( $configs ) )
            {
                $classConfigs = require_once ( $configs );
            } else
            {
                $classConfigs = $configs;
            }
            foreach ( $classConfigs as $instanceName => $config )
            {
                self::$configs[$instanceName] = $config;
            }
        }

        /**
         * Loader::createObject()
         * 
         * @param mixed $className
         * @param mixed $params
         * @return Object or null
         */
        private static function createObject( $className, $params, $initFunction = null )
        {
            if ( ! class_exists( $className ) ){
                trigger_error("The class $className not found!",E_USER_ERROR);
                return false;
            }
            $params = ( ! is_array( $params ) ) ? array( $params ) : $params;
            if ( $initFunction == null )
            {
                $str = ( count( $params ) > 0 ) ? '$' . implode( ',$',
                    array_keys( $params ) ) : '';
                $func = create_function( '$className,$params',
                    'extract($params); 
                    return new $className(' . "$str" . ');' );
                return $func( $className, $params );
            } 
            if ( is_callable( $className, $initFunction ) )
            {
                return call_user_func_array( array( $className, $initFunction ),
                    $params );
            } elseif ( is_callable( $initFunction ) )
            {
                return $initFunction();
            }
            trigger_error("Can't create object $className!",E_USER_ERROR);
        }
    }

}

namespace
{

    use Bulo\Core\Loader\Loader;

    if ( ! function_exists( 'load' ) )
    { //Load a class!!

        /**
         * load()
         * 
         * @param mixed $className
         * @param mixed $params
         * @param mixed $instanceName
         * @param bool $shared
         * @return
         */
        function load( $className, $params = null, $instanceName = null, $shared = true, $initFunction = null )
        {            
            return Loader::load( $className, $params, $instanceName, $shared, $initFunction );
        }
    }
}
