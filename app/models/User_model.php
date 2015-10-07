<?php

namespace models;

use DB\SQL\Mapper;
use wip\DB;

class User_model {
    private $mapper;

    function __construct($data = null){
//        parent::__construct();
        $this->mapper = new Mapper(DB::instance(), "users");

        if(is_array($data)){
            try{
                $user_vars = array(
                    "phone_number" => $data['phone_number'],
                    "password" => $data['password']
                );

                $this->load($user_vars);
            }catch (\Exception $e){
                switch($e->getMessage()){
                    default:
                        throw new \Exception('failed to load user');
                }
            }
        }
    }

    function create($user){
        foreach($user as $key => $value){
            $this->mapper->$key = $value;
        }

//todo        \DB::instance()->pdo()->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        try{
            $this->mapper->save();
        }catch (\PDOException $e){
            switch($e->getMessage()){
                default:
                    throw new \Exception('failed to save new user');
            }
        }

        return $this->mapper->cast();
    }

    function get(){
        try{
            if($this->mapper->dry())
                throw new \Exception('failed to load user');
            return $this->mapper->cast();
        }catch (\Exception $e){
            switch($e->getMessage()){
                default:
                    throw new \Exception('failed to load user');
                    break;
            }
        }
    }

    private function load($data){
        if($this->mapper->load(array('phone_number=? AND password=?',$data['phone_number'],$data['password'])) == false)
            throw new \Exception('failed to load user');
    }
}