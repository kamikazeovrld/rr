<?php

namespace resources;

use wip\Resource;

class Index extends Resource{

    function __construct(){
        parent::__construct();
    }

    function get($base, $args){
        if(isset($args['id'])){
            //id given
            if(isset($args['action'])){
                //action given
            }else{
                //no action given
            }
        }else{
            //no id given
        }
        //all cases
        $this->_show();
    }

    function post($base, $args){

    }

    private function _show(){
        //show dashboard
        echo \View::instance()->render('index.html');
    }

}