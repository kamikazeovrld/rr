<?php

namespace wip;

class DB extends \DB\SQL{
    /**
     *	Return class instance
     *	@return static
     **/
    static function instance() {
        if (!\Registry::exists($class=get_called_class())) {
            $ref=new \Reflectionclass($class);
            $args=func_get_args();
            \Registry::set($class,
                $args?$ref->newinstanceargs($args):new $class);
        }
        return \Registry::get($class);
    }

    function __construct(array $options=NULL){
        //set up connection
        $settings = \Base::instance()->get('db');
        $dsn = 'mysql:host=' . $settings['host'] . ';port=' . $settings['port'] . ';dbname=' . $settings['dbname'];
        parent::__construct($dsn, $settings['user'], $settings['pw']);
    }

} 