<?php

namespace resources;

use wip\Resource;
use models\User_model;

class User extends Resource{

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
        $data = $base->get("POST");

        //add unique to the phone number
        $rules = array(
            "firstname" => 'trim|required',
            "lastname" => 'trim|required',
            "location" => 'trim|required',
            "phone_number" => 'trim|required',
            "gender" => 'trim|required',
            "dob" => 'trim|required',
            "password" => 'trim|required',
            "activation_code" => 'trim|required'
        );


        try{

            foreach($rules as $key => $val){
                \Form_validation::instance()->set_rules($key, $key, $val);
            }

            //validation failed
            if(\Form_validation::instance()->run() == false){
                throw new \Exception('validation failed');
            }

            //successful validation
            $user = $this->getData($data, $rules);
            $model = new User_model();

            $saved_user = $model->create($user);
            if($saved_user == false){
                throw new \Exception('failed to save new user');
            }

            //user successfully saved, send the user data to the device
            $base->set("data", $saved_user);
            echo \Template::instance()->render("json.php");

        }catch (\Exception $e){
            switch($e->getMessage()){
                case 'validation failed':
//                    var_dump(\Form_validation::instance()->error_array());
                    break;
                case 'failed to save new user':
                    echo "yeah, we fucking failed to load the user, how about you try another fucking time";
                    break;
                default:
                    echo 'general error, catching, a rogue function has fucked up in block 59 to 75 no trace';
            }
        }
    }

    private function _show(){
        //show dashboard
        echo \View::instance()->render('index.html');
    }

    private function getData($data, $fields){
        $formatedData = array();

        foreach($fields as $field => $value){
            if(isset($data[$field])){
                $formatedData[$field] = $data[$field];
            }else
                return null;
        }

        return $formatedData;
    }

    private function login($base, $args){
        //user auth

        //send down the user's full user data to populate
    }

}