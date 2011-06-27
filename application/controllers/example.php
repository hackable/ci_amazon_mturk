<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Example extends CI_Controller {




     
      public function index()
     {

      $this->load->library('amazon_mturk',array("sandbox" => FALSE));

         $GetAccountBalanceResponse =  $this->amazon_mturk->GetAccountBalance();

      print_r($GetAccountBalanceResponse);  

     }


}

/* End of file example.php */
/* Location: ./application/controllers/example.php */
