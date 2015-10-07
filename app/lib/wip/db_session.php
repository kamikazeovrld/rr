<?php

namespace wip;

use DB\SQL\Session;

class DB_Session extends \Prefab{



    var $now;
    var $encryption		= FALSE;
    var $sess_length	= 720;
    var $sess_cookie	= 'ci_session'; //cookie name
    var $gc_probability	= 5;
    var $cookie_sent	= FALSE;

    private $session        = null;
    private $flash_key 		= 'flash'; // prefix for "flash" variables (eg. flash:new:message)

    function __construct(){
        $this->base = \Base::instance();
        $this->session =  new Session(DB::instance());

        $this->run();
    }

//session functions
    function run(){
        /*
         *  Set the "now" time
         *
 		 * It can either set to GMT or time(). The pref
         * is set in the config file.  If the developer
         * is doing any sort of time localization they
         * might want to set the session time to GMT so
         * they can offset the "last_activity" and
         * "last_visit" times based on each user's locale.
         *
         */
        if (strtolower($this->base->get('time_reference')) == 'gmt') {
            $now = time();
            $this->now = mktime(gmdate("H", $now), gmdate("i", $now), gmdate("s", $now), gmdate("m", $now), gmdate("d", $now), gmdate("Y", $now));

            if (strlen($this->now) < 10) {
                $this->now = time();
            }
        } else {
            $this->now = time();
        }



        /*
         *  Set the session length
         *
         * If the session expiration is set to zero in
         * the config file we'll set the expiration
         * //two years from now.
         * 5 minutes from now
         *
         */
        $expiration = $this->base->get('sess_expiration');

        if (is_numeric($expiration)) {
            if ($expiration > 0) {
                $this->sess_length = $expiration;
            } else {
                $this->sess_length = 720;
            }
        }

        //check session validity
        // 1. check existance in table
        // 2. check expiration

        if($this->session->stamp() + $this->sess_length < $this->now){
            session_destroy();
            $this->session = new Session(DB::instance());
        }


        //run garbage collection if probability met
        $this->gc();

        // delete old flashdata (from last request)
        $this->sweep_flash_data();

        // mark all new flashdata as old (data will be deleted before next request)
        $this->mark_flash_data();

    }

    /**
     * Garbage collection
     *
     * This deletes expired session rows from database
     * if the probability percentage is met
     *
     * @access	public
     * @return	void
     */
    function gc(){
        if ((rand() % 100) < $this->gc_probability) {
            $this->session->cleanup($this->sess_length);
        }
    }

//data
    function set_data($data = array(), $val = ''){
        if (is_string($data))
        {
            $data = array($data => $val);
        }

        if (count($data) > 0)
        {
            foreach ($data as $key => $val)
            {
                $this->base->set('SESSION.'.$key, $val);
            }
        }
    }

    function unset_data($data = array()){
        if (is_string($data))
        {
            $data = array($data => '');
        }

        if (count($data) > 0)
        {
            foreach ($data as $key => $val)
            {
                $this->base->clear('SESSION["'.$key.'"]');
            }
        }
    }

    function get_data($key = ''){
        if ( !strlen($key) )
            return $this->base->get('SESSION');
        return $this->base->get('SESSION.'.$key);
    }

//flash data
    function set_flash_data($data = array(), $val){
        if (is_string($data))
        {
            $data = array($data => $val);
        }

        if (count($data) > 0)
        {
            foreach ($data as $key => $val)
            {
                $flash_key = $this->flash_key.':new:'.$key;
                $this->set_data($flash_key, $val);
//                $this->base->set('SESSION.'.$key, $val);
            }
        }
    }

    function get_flash_data($key='')
    {
        if( !strlen($key) )
            return false;
        $flash_key = $this->flash_key.':old:'.$key;
        return $this->get_data($flash_key);
    }

    function sweep_flash_data(){
        $data = $this->get_data();
        foreach ($data as $key => $value) {
            $parts = explode(':old:', $key);
            if ( (is_array($parts)) && (count($parts) == 2) && ($parts[0] == $this->flash_key)){
                $this->unset_data($key);
            }
        }
    }

    function keep_flash_data($key){
        $old_flash_key = $this->flash_key.':old:'.$key;
        $new_flash_key = $this->flash_key.':new:'.$key;
        $value = $this->get_data($old_flash_key);

        $this->set_data($new_flash_key, $value);
    }

    function mark_flash_data(){
        $data = $this->get_data();
        foreach ($data as $key => $val)
        {
            $parts = explode(':new:', $key);
            if (is_array($parts) && count($parts) == 2)
            {
                $new_key = $this->flash_key.':old:'.$parts[1];
                $this->set_data($new_key, $val);
                $this->unset_data($key);
            }
        }
    }

}