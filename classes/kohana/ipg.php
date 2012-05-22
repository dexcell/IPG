<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_IPG {
  
  protected $_config;
  protected $_data = array();
  
  public static function factory()
  {
    return new IPG;
  }
  
  public function __construct()
  {
    $this->_config = Kohana::$config->load('ipg');
  }
  
  public function set($key, $value)
  {
    $this->_data[$key] = $value;
   
    return $this;
  }
  
  public function values($values)
  {
    $this->_data = $values;
    
    return $this;
  }
  
  /*
   * Render
   * 
   * $ipg = IPG::factory()
   *  ->values(array(
   *    'invoice' => 1,
   *    'amount' => 950000,
   *    'currency_code' => 'IDR',
   *    'language' => 'EN',
   *    'cust_id' => 1,
   *  ))
   *  ->render();
   * 
   */
  public function render()
  {
    if ( ! isset($this->_data['language']))
    {
      $this->_data['language'] = 'EN';
    }
    
    $merchant_id = $this->_config->merchant_id;
    $merchant_password = $this->_config->merchant_password;
    $amount = Arr::get($this->_data, 'amount');
    $invoice = Arr::get($this->_data, 'invoice');
    
    $values = Arr::merge(array(
      'trax_date' => date('YmdHis'),
      'trax_type' => 'Payment',
      'merchant_id' => $this->_config->merchant_id,
      'timeout' => $this->_config->timeout,
      'mer_signature' => strtoupper(hash('sha256', "$merchant_id%$merchant_password%PAYMENT%$amount%$invoice")),
      'return_url' => $this->_config->return_url,
    ), $this->_data);
    
    return View::factory('ipg/base')
      ->set('payment_url', $this->_config->payment_url)
      ->set('query_string', URL::query($values, FALSE))
      ->render();
  }
  
}