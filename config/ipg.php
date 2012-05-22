<?php defined('SYSPATH') or die('No direct access allowed.');

return array(
  'merchant_id' => '', // Merchant ID
  'merchant_password' => '', // Merchant Password
  'timeout' => 20, // In Minutes
  'payment_url' => 'https://demos.finnet-indonesia.com/telkom/PaymentUser.action', 
  'return_url' => 'http://yourwebsite.com/api/ipg', // Return URL after user do the transaction
);
