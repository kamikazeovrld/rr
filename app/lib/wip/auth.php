<?php

namespace wip;

use models\User_model;
use models\User_profile_model;
use models\User_temp_model;

class Auth extends \Prefab{

    function __construct(){
        $this->base = \Base::instance();

        $this->db_session       = DB_Session::instance();
        $this->user_model       = new User_model();
        $this->user_temp_model  = new User_temp_model();

        if($this->base->get('auth.create_user_profile'))
            $this->user_profile_model = new User_profile_model();

        $this->_init();
    }



    //private functions
//initialize
    private function _init(){

    }

    private function _redirect($action){
        if($action == 'login'){
            $this->base->reroute($this->base->get('auth.login_success_action)'));
        }

        if($action == 'logout'){
            $this->base->reroute($this->base->get('auth.logout_success_action)'));
        }

        if($action == 'pre_login'){
            $this->base->reroute($this->base->get('auth.login_uri'));
        }

        if($action == 'pre_login_denied'){
            $this->base->reroute($this->base->get('auth.denied_from_ext_location'));
        }

        if($action == 'pre_login_ext'){
            $this->base->reroute($page = $this->base->get('auth.denied_page'));
        }

        exit('err');
    }

//functions strings
    /*private */function _encode($password){
        $salt=null;

        // if you set your encryption key let's use it
        $key = $this->base->get('encryption_key');
        if ( $key != '' ) {
            // concatenates the encryption key and the password
            $_password = $key.$password;
        } else {
            $_password=$password;
        }

        // PHP5 only
        $_pass = str_split($_password);

        // encrypts every single letter of the password
        foreach ($_pass as $_hash)
        {
            $salt .= md5($_hash);
        }

        // encrypts the string combinations of every single encrypted letter
        // and finally returns the encrypted password
        return $password=md5($salt);
    }

    private function _generateRandomString(){
        $charset = "abcdefghijklmnopqrstuvwxyz";
        if ($this->base->get('auth.captcha_upper_lower_case'))
            $charset .= "ABCDEFGHIJKLMNPQRSTUVWXYZ";
        if ($this->base->get('auth.captcha_use_numbers'))
            $charset .= "23456789";
        if ($this->base->get('auth.captcha_use_specials'))
            $charset .= "~@#$%^*()_+-={}|][";

        $length = mt_rand($this->base->get('auth.captcha_min'), $this->base->get('auth.captcha_max'));
        if ($this->base->get('auth.captcha_min') > $this->base->get('auth.captcha_max'))
            $length = mt_rand($this->base->get('auth.captcha_max'), $this->base->get('auth.captcha_min'));

        $key = '';
        for ($i = 0; $i < $length; $i++)
            $key .= $charset[(mt_rand(0, (strlen($charset)-1)))];

        return $key;
    }

//functions email
    private function _sendEmail($email, $subject, $message){
        return true;
    }

    private function _sendActivationEmail($id, $user, $password_email, $email, $activation_code){
        return true;
    }

    private function _sendForgottenPasswordEmail($id, $user, $email, $activation_code){
        return true;
    }

    private function _sendForgottenPasswordResetEmail($id, $user, $email, $password){
        return true;
    }

    private function _sendChangePasswordEmail($id, $user, $email, $password_email) {
        return true;
    }

//functions session
    private function _set_user($user_data){
        //updates the Last_visit field in the user table
        $this->user_model->login($user_data['id']);

        //sets the user data to the session
        $this->db_session->set_data($user_data);

        return true;
    }

    private function _unset_user($user_data){
        $user_name = $this->db_session->get_data('user_name');

//todo input is unused        if ($user_data['user_name'] == $user_name) {
        if (true) {

            $fields = array('id', 'user_name', 'country_id', 'email', 'role', 'last_visit', 'created', 'modified');

            $this->db_session->unset_data($fields);

        }

    }

//functions form data
    private function _get_login_form()
    {
        $values['email']    = $this->base->get('POST.email');
        $values['password']     = $this->base->get('POST.password');

        //$values[$this->base->get('config.FAL_<your field>_field')] = $this->CI->input->post($this->base->get('config.FAL_<your field>_field'));

        return $values;
    }

    private function _get_registration_form()
    {
        $values['user_name']    = $this->base->get('POST.user_name');
        $values['password']     = $this->base->get('POST.password');
        $values['email']        = $this->base->get('POST.email');

        if ($this->base->get('auth.use_country'))
            $values['country_id'] = $this->base->get('POST.country_id');

        //$values[$this->base->get('config.FAL_<your field>_field')] = $this->CI->input->post($this->base->get('config.FAL_<your field>_field'));

        return $values;
    }

//functions other
    private function _clean_expired_user_temp(){
        $expiration = $this->base->get('auth.temporary_users_expiration');

        return $this->user_temp_model->drop_expired($expiration);
    }

    //public functions
//functions access level
    //todo redirects
    private function _deny_access($role = ''){
        if ($this->base->get('auth.deny_with_flash_message')) {
            // visitor is a GUEST
            if ($role == '') {
                $msg = $this->base->get('lang.no_credentials_guest');
                $this->db_session->set_flash_data('flashMessage', $msg);

                // store the requested page in order
                // to serve it back to the visitor after a successful login.
                $this->db_session->set_flash_data('requested_page', $this->base->get('URI'));

                // Then we redirect to the login form with a 'access denied'
                // message. Maybe if the visitor can log in,
                // he'll get some more permissions...
                $this->_redirect('pre_login');
            }
            // visitor is a USER
            else {
                $msg = $this->base->get('lang.no_credentials_user');
                $this->db_session->set_flash_data('flashMessage', $msg);

                // if visitor came to this site with an http_referer
                //todo this block is a total mess
                if ($this->base->get('SERVER.HTTP_REFERER')){
                    $referer = $this->base->get('SERVER.HTTP_REFERER');
                    if (preg_match("|^".$this->base->get('BASE_URL')."|", $referer) == 0) {
                        // if http_referer is from an external site,
                        // users are taken to the page defined in the config file
                        $this->_redirect('pre_login_ext');
                    }else{
                        // if we came from our website, just go to this page back
                        // but maybe we arrived here because of the
                        // 'redirect to requested page', so in order not to
                        $this->db_session->keep_flash_data('requested_page');
                        header("location:".$this->base->get('SERVER.HTTP_REFERER'));
                    }
                }
                // if visitor did not come to this site with an http_referer,
                // redirect to the page defined in the config file too
                else {
                    $this->_redirect('pre_login_ext');
                }
            }
        }
        else {
            $this->_redirect('pre_login_denied');
        }
    }

    function belongsToGroup($_group=null, $_only=null){
        if ($this->db_session AND $this->base->get('auth.FAL')){
            //get current user name and role
            $username   = $this->db_session->get_data('user_name');
            $who_is     = $this->db_session->get_data('role');

            //there is a currently logged in user
            if($username != false AND $who_is != false){
                if ($_group==null)
                    $_group='user';

                $_groups = explode(",", $_group);

                foreach($_groups as $key => $val) {
                    $_groups[$key] = trim($val);
                }

                if($_only){
                    $condition = in_array($who_is, $_groups) ? true : false;
                }else{
                    $hierarchy = $this->base->get('auth.roles');
                    foreach ($_groups as $role){
                        $group_hierarchy[] = $hierarchy[$role];
                    }

                    $group_hierarchy = max($group_hierarchy);

                    $who_is_hierarchy = $hierarchy[$who_is];

                    $condition = $who_is_hierarchy <= $group_hierarchy ? true : false;
                }

                return $condition;

            }
        }
        // if condition==false, db_session turner off or user not found (namely not logged in) in ci_session

        return false;
    }

    function isUser(){

        if ($this->db_session AND $this->base->get('auth.FAL'))
        {
            $_username  = $this->db_session->get_data('user_name');
            $_role      = $this->db_session->get_data('role');

            if ($_username != false && $_role != false AND ($_role=='user' OR $_role=='admin' OR $_role=='superadmin'))

                //returns the user id
                return true;
        }

        // if user_id not activated or not existent
        return false;
    }

    function isAdmin(){

        if ($this->db_session AND $this->base->get('auth.FAL'))
        {
            $_username  = $this->db_session->get_data('user_name');
            $_role      = $this->db_session->get_data('role');

            if ($_username != false && $_role != false AND ($_role=='admin' OR $_role=='superadmin'))

                //returns the user id
                return true;
        }

        // if user_id not activated or not existent
        return false;
    }

    function isSuperAdmin(){

        if ($this->db_session AND $this->base->get('config.FAL'))
        {
            $_username  = $this->db_session->get_data('user_name');
            $_role      = $this->db_session->get_data('role');

            if ($_username != false AND $_role != false AND $_role=='superadmin')

                return true;
        }

        return false;
    }

//functions user processes
    function check($_lock_to_role=null, $_only=null){
        //guest only
        /*if($_lock_to_role == 'guest' AND $_only){
            if($this->isUser())
                $this->denyAccess();
        }*/

        //not logged in
        if(!$this->isUser())
            $this->_deny_access();

        //belongs to group
        if(!$this->belongsToGroup($_lock_to_role, $_only)){
            //get the role
            $role      = $this->db_session->get_data('role');
            $this->_deny_access($role);
        }
    }


    /*function check_permission($right){
        $_who_is = $this->CI->db_session->userdata('role');
        $id = $this->CI->db_session->userdata('id');

        if ($this->CI->config->item('FAL') && $this->CI->db_session) {
            $user_profile = $this->_getUserProfile($id);
            if($user_profile[$right] == TRUE || $this->isAdmin()){
                // nothing
            }else{
                $this->denyAccess($_who_is);
            }
        }else {
            $this->denyAccess($_who_is);
        }

    }*/

    function login(){
        if (!$this->base->get('auth.FAL')) {
            //authentication is turned off
            return false;
        }

        $message = $this->base->get('lang.invalid_user_message');

        if ($this->db_session) {
            //session object exists and is instantiated
            $values = $this->_get_login_form();
            $email = (isset($values['email']) ? $values['email'] : false);
            $password = (isset($values['password']) ? $values['password'] : false);

            if (($email != false) && ($password != false)){
                //credentials have been submitted
                $password = $this->_encode($password);

                // Use the input email and password and check against
                // 'user' table to check if user exists and has correct credentials
                $query = $this->user_model->load(array(
                    'email'     => $email,
                    'password'  => $password
                ));

                //user found and has correct credentials
                if ($query != false) {
                    $user_data_temp = $query->cast();
                    $fields = array('id', 'user_name', 'country_id', 'email', 'role', 'last_visit', 'created', 'modified');

                    foreach($fields as $field) $user_data[$field] = $user_data_temp[$field];

                    // verifies if a user has not been banned from the site
                    if ($user_data_temp['banned'] == 0) {
                        $this->_set_user($user_data);

                        // set FLASH MESSAGE successful login
                        $this->db_session->set_flash_data('flashMessage', $this->base->get('lang.login_message'));
                        $this->_redirect('login');
                    }
                    //user is banned from site
                    else {
                        $message = $this->base->get('lang.banned_user_message');
                    }
                }
            }
        }

        //fail cases
        // 1. no session object
        // 2. no credentials submitted through post form
        // 3. incorrect credentials, email or password or both
        // 4. user banned from site
        // 5. authentication turned off(not yet there)

        // On error send user back to login page, and add error message
        // set FLASH MESSAGE
        $this->db_session->set_flash_data('flashMessage', $message);
        // todo : handle fail cases
        return false;
    }

    function logout(){
        // checks if a session exists and is initialised
        if ($this->db_session){
            $_username = $this->db_session->get_data('user_name');

            if ($_username != false)
                // deletes the user_data stored in DB for the user that logged out
                $this->_unset_user($_username);
//           todo return true on successful logout
//            return true;
        }

        //fail cases:
        //1. no session object
        //2. no currently logged in user

        // set FLASH MESSAGE
        $msg = $this->base->get('lang.logout_message');
        $this->db_session->set_flash_data('flashMessage', $msg);

        //todo handle correct redirect, depending on case
        $this->_redirect('logout');
    }

    //todo, making auth return id on success
    function register(){
        //todo there are scenarios here that do not return a value
        // let's clean the user_temp table
        // if we use registration with e-mail verification
        if (!$this->base->get('auth.register_direct')){
            $this->_clean_expired_user_temp();
        }

        // let's check if the system is turned on and if we allow users to register
        if (!$this->base->get('auth.FAL') OR $this->base->get('auth.allow_user_registration')!=TRUE)
            return false;

        if ($this->db_session){
            $values     = $this->_get_registration_form();
            $username   = (isset($values['user_name']) ? $values['user_name'] : false);
            $password   = (isset($values['password']) ? $values['password'] : false);
            $email      = (isset($values['email']) ? $values['email'] : false);

            if (($username != false) && ($password != false) && ($email != false)){
                $values['password'] = $this->_encode($password);

                // if we go for standard activation with e-mail verification
                // namely i.e. $config['FAL_register_direct'] = FALSE
                if (!$this->base->get('auth.register_direct')) {
                    // generates the activation code
                    $values['activation_code'] = $this->_generateRandomString();
                    $query = $this->user_temp_model->add_new($values);

                    if ( $query != false ) {

                        $this->_sendActivationEmail($query->id, $username, $password, $email, $values['activation_code']);

                        return true;
                    }
                }
                // do we skipp e-mail verification?
                // namely if we go for direct activation
                // i.e. $config['FAL_register_direct'] = TRUE
                else {
                    // let's insert the values in the user table
                    $query = $this->user_model->add($values);

                    if ($query != false) {
                        // if we want the user profile as well
                        if($this->base->get('auth.create_user_profile')) {
                            $user_id = $query->id;
                            $data_profile['id'] = $query->id;
                            $this->user_profile_model->add_new($data_profile);

                            $this->db_session->set_flash_data('flashMessage', $this->base->get('lang.activation_success_message'));
//                        return true;
                            return $user_id;
                        }
                    }

                }
            }
            else {
                // set FLASH MESSAGE
                $this->db_session->set_flash_data('flashMessage', $this->base->get('lang.invalid_register_message'));
                // FIXME : if false is returned, no redirection is done in FAL_front
                return false;
            }
        }
        return true;
    }

    function activate($id, $activation_code){
        return true;
    }

}