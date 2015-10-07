<?php

namespace resources;

use wip\Resource;
use wip\Form_validation;
use models\User_model;

class Auth extends Resource{

    //add unique to the phone number
    private $register_rules = array(
        "firstname" => 'trim|required',
        "lastname" => 'trim|required',
        "location" => 'trim|required',
        "phone_number" => 'trim|required',
        "gender" => 'trim|required',
        "dob" => 'trim|required',
        "password" => 'trim|required',
        "activation_code" => 'trim|required'
    );
    private $login_rules = array(
        "phone_number" => 'trim|required',
        "password" => 'trim|required'
    );

    function __construct(){
        parent::__construct();
    }

    function get($base, $args){
        var_dump($args);
        $this->route($base, $args);
    }

    function post($base, $args){
        var_dump($args);
        $this->route($base, $args);
    }

    private function _show($base, $data){
        $base->set('data', $data);

        //send json ouput
        echo \Template::instance()->render('json.php');
    }

    private function getData($data, $fields){
        $formatedData = array();

        foreach($fields as $field => $value){
            if(isset($data[$field])){
                $formatedData[$field] = $data[$field];
            }else
                throw new \Exception('field not found');
        }

        return $formatedData;
    }

    private function route($base, $args){
        $action = isset($args['action']) ? $args['action'] : null;

        switch($action){
            case 'register':
                $this->register($base, $args);
                break;
            case 'login':
                $this->login($base, $args);
                break;
            case 'reset':
                break;
            case null;
                break;
            default:
                break;
        }
    }

    private function login($base, $args){
        try{
            $user_data = $this->formValidation($base, 'login');

            //add to model
            $model = new User_model($user_data);

            $saved_user_data = $model->get();

            $this->_show($base, $saved_user_data);

        }catch (\Exception $e){
            switch($e->getMessage()){
                case 'user not found';
                    echo 'user not found';
                    break;
            }
        }

        //send down the user's full user data to populate
    }

    private function register($base, $args){
        try{
            $user_data = $this->formValidation($base, 'registration');

            //add to model
            $model = new User_model();

            $saved_user_data = $model->create($user_data);

            $this->_show($base, $saved_user_data);

        }catch (\Exception $e){
            switch($e->getMessage()){
                case 'field not found';
                    echo 'field not found';
                    exit;
                    break;
                case 'failed to save new user';
                    echo 'failed to save new user';
                    exit;
                    break;
            }
        }


    }

    private function reset($base, $args){

    }

    private function formValidation($base, $form){
        $data = $base->get("POST");

        try{
            switch($form){
                case 'registration':
                    $rules = $this->register_rules;
                    break;
                case 'login':
                    $rules = $this->login_rules;
                    break;
                default:
                    throw new \Exception('no validation rules found');
                    break;
            }


            foreach($rules as $key => $val){
                Form_validation::instance()->set_rules($key, $key, $val);
            }

            //validation failed
            if(Form_validation::instance()->run() == false){
                throw new \Exception('validation failed');
            }

            //successful validation
            $user = $this->getData($data, $rules);

            return $user;

        }catch (\Exception $e){
            switch($e->getMessage()){
                case 'validation failed':
//                    var_dump(Form_validation::instance()->error_array());
                    break;
                case 'no validation rules found':
                    echo 'no validation rules found';
                    break;
//                case 'field not found':
//                    echo 'field not found';
//                    exit;
//                    break;
                default:
                    echo 'general error, catching, a rogue function has fucked up in block 59 to 75 no trace';
            }
        }
    }

}