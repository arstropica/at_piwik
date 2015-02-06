<?php
    /**
    * EndPoint Class
    */

    class EndPoint_AT_Piwik 
    extends AT_Piwik 
    {

        /**
        *    @var array Results
        */
        private $log;


        /**
        *    @var string API Key
        */
        protected $api_key;


        /** Hook WordPress
        *    @return void
        */
        public function __construct($api_key){
            $this->api_key = $api_key;
            add_filter('query_vars', array($this, 'add_query_vars_EndPoint_AT_Piwik'), 0);
            add_action('parse_request', array($this, 'sniff_requests_EndPoint_AT_Piwik'), 0);
            add_action('init', array($this, 'add_endpoint_EndPoint_AT_Piwik'), 0);
        }    

        /** Add public query vars
        *    @param array $vars List of current public query vars
        *    @return array $vars 
        */
        public function add_query_vars_EndPoint_AT_Piwik($vars){
            $vars[] = 'at_piwik';
            $vars[] = '__api';
            $vars[] = 'at_piwik_action';
            $vars[] = 'at_piwik_domain';
            $vars[] = 'at_piwik_value';
            $vars[] = 'at_piwik_mode';
            $vars[] = 'at_piwik_idsite';
            return $vars;
        }

        /** Add API Endpoint
        *    This is where the magic happens - brush up on your regex skillz
        *    @return void
        */
        public function add_endpoint_EndPoint_AT_Piwik(){
            add_rewrite_rule('^at_piwik_api/?\?(.*)','index.php?at_piwik=1&$matches[1]','top');
        }

        /** Initialize Log
        *    Setup Log Array
        *    @return void
        */
        public function init_log_EndPoint_AT_Piwik(){
            
            global $wp;
            
            remove_all_actions( 'shutdown' );

            $this->log = array(
                'results' => array(),
                'response' => 0,
                'message' => '',
                'request' => $wp->query_vars
            );

        }

        /** Add to Log
        *    Add to Log Array
        *    @return void
        */
        public function append_log_EndPoint_AT_Piwik($blog_id, $at_piwik_api_idsite, $at_piwik_api_domain, $at_piwik_api_action, $at_piwik_api_value, $at_piwik_api_outcome){

            $entry = array(
                'idsite' => $at_piwik_api_idsite,
                'domain' => $at_piwik_api_domain,
                'action' => $at_piwik_api_action,
                'value' => $at_piwik_api_value,
                'outcome' => $at_piwik_api_outcome
            );

            if ($blog_id) {
                $this->log['results'][$blog_id] = $entry;
            } else {
                $blog_id = md5(microtime().rand());
                $this->log['results'][$blog_id] = $entry;
            }

        }

        /**    Sniff Requests
        *    This is where API requests are processed
        *     If $_GET['__api'] is set, we can set plugin option(s)
        *    @return die if API request
        */
        public function sniff_requests_EndPoint_AT_Piwik(){
            global $wp;
            if(isset($wp->query_vars['at_piwik'])){

                $this->init_log_EndPoint_AT_Piwik();

                $at_piwik_api_request_key = isset($wp->query_vars['__api']) ? $wp->query_vars['__api'] : false;
                $at_piwik_api_auth = ($at_piwik_api_request_key == $this->api_key);
                
                if ($at_piwik_api_auth) {
                    $at_piwik_api_idsite = isset($wp->query_vars['at_piwik_idsite']) ? $wp->query_vars['at_piwik_idsite'] : false;
                    $at_piwik_api_action = isset($wp->query_vars['at_piwik_action']) ? $wp->query_vars['at_piwik_action'] : false;
                    $at_piwik_api_domain = isset($wp->query_vars['at_piwik_domain']) ? $wp->query_vars['at_piwik_domain'] : false;
                    $at_piwik_api_value = isset($wp->query_vars['at_piwik_value']) ? $wp->query_vars['at_piwik_value'] : false;                

                    $mode = isset($wp->query_vars['at_piwik_mode']) ? $wp->query_vars['at_piwik_mode'] : 'single';

                    switch ($mode) {
                        case 'admin' : {
                            $this->handle_admin_request_EndPoint_AT_Piwik($at_piwik_api_domain, $at_piwik_api_action, $at_piwik_api_value);
                            break;
                        }
                        case 'single' : {
                            $this->handle_request_EndPoint_AT_Piwik($at_piwik_api_idsite, $at_piwik_api_domain, $at_piwik_api_action, $at_piwik_api_value);
                            break;
                        }
                        case 'multi' : {
                            $this->handle_requests_EndPoint_AT_Piwik($at_piwik_api_idsite, $at_piwik_api_domain, $at_piwik_api_action, $at_piwik_api_value);
                            break;
                        }
                        default : {
                            $this->handle_request_EndPoint_AT_Piwik($at_piwik_api_idsite, $at_piwik_api_domain, $at_piwik_api_action, $at_piwik_api_value);
                            break;
                        }
                    }
                } else {
                    $this->set_response_EndPoint_AT_Piwik(401, 'Invalid or missing API Key.');
                }
                $this->send_response_EndPoint_AT_Piwik();
                exit;
            }
        }

        /** Handle Requests
        *    This is where we handle bulk requests
        *    @return void 
        */
        protected function handle_requests_EndPoint_AT_Piwik($at_piwik_api_idsite_enc, $at_piwik_api_domains_enc, $at_piwik_api_actions_enc, $at_piwik_api_values_enc){

            $outcome = true;

            if (! $at_piwik_api_idsite_enc || ! $at_piwik_api_domains_enc || ! $at_piwik_api_actions_enc || ! $at_piwik_api_values_enc ) {

                $this->append_log_EndPoint_AT_Piwik(0, $at_piwik_api_idsite_enc, $at_piwik_api_domains_enc, $at_piwik_api_actions_enc, $at_piwik_api_values_enc, 0);

                $this->set_response_EndPoint_AT_Piwik(400, 'One or more parameters are missing.');

                return;

            }

            parse_str(urldecode($at_piwik_api_idsite_enc), $at_piwik_api_idsite);
            parse_str(urldecode($at_piwik_api_domains_enc), $at_piwik_api_domains);
            parse_str(urldecode($at_piwik_api_actions_enc), $at_piwik_api_actions);
            parse_str(urldecode($at_piwik_api_values_enc), $at_piwik_api_values);

            if (! $at_piwik_api_idsite || ! $at_piwik_api_actions || ! $at_piwik_api_domains || ! $at_piwik_api_values ) {

                $this->append_log_EndPoint_AT_Piwik(0, $at_piwik_api_idsite_enc, $at_piwik_api_domains_enc, $at_piwik_api_actions_enc, $at_piwik_api_values_enc, 0);

                $this->set_response_EndPoint_AT_Piwik(400, 'One or more parameters are malformed.');

                return;

            }

            for ($index = 0; $index < count($at_piwik_api_idsite); $index ++) {

                $result = $this->handle_request_EndPoint_AT_Piwik($at_piwik_api_idsite[$index], $at_piwik_api_domains[$index], $at_piwik_api_actions[$index], $at_piwik_api_values[$index]);

                $outcome = $result ? $outcome : false;

            }

            if ($outcome) {

                $this->set_response_EndPoint_AT_Piwik(200, 'Update Successful.');

            } else {

                $this->set_response_EndPoint_AT_Piwik(207, 'Update partially successful.');

            }

        }

        /** Handle Admin Request
        *    This is where site admin options are set
        *    @return void 
        */
        protected function handle_admin_request_EndPoint_AT_Piwik($at_piwik_api_domain, $at_piwik_api_action, $at_piwik_api_value){

            $blog_id = 0;

            if (! $at_piwik_api_action) {

                $this->set_response_EndPoint_AT_Piwik(400, 'One or more parameters are missing.');

                return false;

            }

            if (is_multisite()) {

                update_site_option("at_piwik_{$at_piwik_api_action}", $at_piwik_api_value);

            } else {

                update_option("at_piwik_{$at_piwik_api_action}", $at_piwik_api_value);

            }

            $this->set_response_EndPoint_AT_Piwik(200, 'Admin Update successful.');

            return true;

        }

        /** Handle Request
        *    This is where site options are set
        *    @return void 
        */
        protected function handle_request_EndPoint_AT_Piwik($at_piwik_api_idsite, $at_piwik_api_domain, $at_piwik_api_action, $at_piwik_api_value){

            $blog_id = 0;

            if (is_multisite() && $at_piwik_api_domain) {

                $blog_id = $this->get_blogid_EndPoint_AT_Piwik($at_piwik_api_domain);

                if ($blog_id) {

                    switch_to_blog($blog_id);

                } else {

                    $this->append_log_EndPoint_AT_Piwik($blog_id, $at_piwik_api_idsite, $at_piwik_api_domain, $at_piwik_api_action, $at_piwik_api_value, 0);

                    $this->set_response_EndPoint_AT_Piwik(410, 'A blog matching this domain could not be found.');

                    return false;

                }

            } 

            if (! $at_piwik_api_action || ! $at_piwik_api_domain || ! $at_piwik_api_idsite) {

                $this->append_log_EndPoint_AT_Piwik($blog_id, $at_piwik_api_idsite, $at_piwik_api_domain, $at_piwik_api_action, $at_piwik_api_value, 0);

                $this->set_response_EndPoint_AT_Piwik(400, 'One or more parameters are missing.');

                return false;

            }

            update_option("{$at_piwik_api_action}", $at_piwik_api_value);

            update_option("at_piwik_idsite", $at_piwik_api_idsite);

            update_option("at_piwik_last_update", time());

            $this->append_log_EndPoint_AT_Piwik($blog_id, $at_piwik_api_idsite, $at_piwik_api_domain, $at_piwik_api_action, $at_piwik_api_value, 1);

            $this->set_response_EndPoint_AT_Piwik(200, 'Update successful.');

            return true;
        }

        /**
        * Get blog id from domain
        * 
        * @param $domain the blog's domain
        */
        public function get_blogid_EndPoint_AT_Piwik($domain) {
            global $wpdb;

            $blog_id = 0;

            $is_multisite = is_multisite();

            if (! $is_multisite)
                return $blog_id;

            $dmtable = $wpdb->base_prefix . 'domain_mapping';

            if ($wpdb->get_var("SHOW TABLES LIKE '$dmtable'") == $dmtable) {
                $blog_id = $wpdb->get_var( "SELECT blog_id FROM {$wpdb->dmtable} WHERE domain = '{$domain}' LIMIT 1" );
            }

            if (! $blog_id) {
                $blog_id = $wpdb->get_var( "SELECT blog_id FROM {$wpdb->blogs} WHERE domain = '{$domain}' LIMIT 1" );
            }

            return $blog_id;

        }

        /** Response Handler
        *    This sets an HTTP response code and message in the log
        */
        protected function set_response_EndPoint_AT_Piwik($code, $msg){

            $this->log['response'] = $code;
            $this->log['message'] = $msg;

        }

        /** Response Handler
        *    This sends a JSON response to the browser
        */
        protected function send_response_EndPoint_AT_Piwik(){
            if ($this->log['response'])
                http_response_code($this->log['response']);
            header('content-type: application/json; charset=utf-8');
            echo json_encode($this->log)."\n";
            exit;
        }
        
        /**
        * Return API Key
        * 
        * @return string API Key
        * 
        */
        public function get_api_key_EndPoint_AT_Piwik() {
            
            return $this->api_key;
            
        }
    }
