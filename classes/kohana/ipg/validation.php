<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_IPG_Validation {
  
  protected $_config;
  protected $_error;
  protected $_rules = array();
  protected $_values = array();
  
  public static function factory(array $values)
  {
    return new IPG_Validation($values);
  }
  
  public function __construct($values)
  {
    $this->_config = Kohana::$config->load('ipg');
    $this->_values = $values;
  }
  
  public function rule($key, $value)
  {
    $this->_rules[$key] = $value;
   
    return $this;
  }
  
  public function rules($values)
  {
    $this->_rules = $values;
    
    return $this;
  }
  
  public function error()
  {
    return $this->_error;
  }
  
  /*
   * Validate IPG response
   * 
   *  $validation = IPG_Validation::factory($this->request->post())
   *    ->rule('invoice', 1)
   *    ->rule('amount', 950000)
   *    ->rule('currency_code', 'IDR');
   * 
   *  if ($validation->check())
   *  {
   *    // Do something
   *  }
   *  else
   *  {
   *    echo $validation->error();
   *  }
   * 
   * return boolean
   * 
   */
  public function check()
  {
    $result = FALSE;
    
    try
    {
      if (Arr::get($this->_values, 'result_code') != '0')
        throw new Kohana_Exception(trim(Arr::get($this->_values, 'result_desc', 'Result code not found.').' '.Arr::get($this->_values, 'vpc_acqdesc')));
      
      if (Arr::get($this->_values, 'merchant_id') != $this->_config->merchant_id)
        throw new Kohana_Exception('Invalid merchant ID');
      
      $merchant_id = $this->_config->merchant_id;
      $merchant_password = $this->_config->merchant_password;
      $amount = Arr::get($this->_values, 'amount');
      $invoice = Arr::get($this->_values, 'invoice');
      
      $merchant_signature = strtoupper(hash('sha256', "$merchant_id%$merchant_password%PAYMENT%$amount%$invoice"));
      
      if (Arr::get($this->_values, 'mer_signature') != $merchant_signature)
        throw new Kohana_Exception('Invalid merchant signature');

      foreach ($this->_rules as $key => $rule)
      {
        if ($value = Arr::get($this->_values, $key) != $rule)
          throw new Kohana_Exception("$key invalid");
      }
      
      // Check the transaction is valid or not
      // Create merchant signature
      $merchant_signature = strtoupper(hash('sha256', "$merchant_id%$merchant_password%CHECKSTAT%$invoice"));
      
      $values = array(
        'trax_date' => date('YmdHis'),
        'trax_type' => 'CheckStat',
        'merchant_id' => $this->_config->merchant_id,
        'invoice' => $invoice,
        'mer_signature' => $merchant_signature,
      );

      $request = Request::factory($this->_config->payment_url)
        ->method(HTTP_Request::POST)
        ->post($values);

      $request->client()
        ->options(array(
          CURLOPT_SSL_VERIFYHOST => 0,
          CURLOPT_SSL_VERIFYPEER => 0,
        ));

      $response = $request->execute();
      
      $query_string = explode('&', $response->body());
      
      $statuses = array();
      foreach ($query_string as $chunk)
      {
        if ($chunk)
        {
          list($key, $value) = explode('=', $chunk);
          $statuses[$key] = $value;
        }
      }
      
      if (Arr::get($statuses, 'result_code') != '0')
        throw new Kohana_Exception(Arr::get($statuses, 'result_desc', 'Status result code not found'));
      
      $result = TRUE;
    }
    catch (Kohana_Exception $e)
    {
      $this->_error = $e->getMessage();
    }
    
    return $result;
  }
  
}