<?php
namespace bsctool;

use Exception;
use Web3\Contract;
use Web3\Utils;

class Transactor{
  protected $web3;
  
  //context
  protected $gasPrice;
  protected $gasLimit;
  protected $value;
  protected $credential;
    
  public function __construct($web3, $credential){
    $this->web3 = $web3;
    $this->credential = $credential;    
  }
  
  public function setGasPrice($price = null){
    $this->gasPrice = $price;
    return $this;
  }
  
  public function setGasLimit($limit = null){
    $this->gasLimit = $limit;
    return $this;
  }
  
  public function setValue($value = null){
    $this->value = $value;
    return $this;
  }
  
  public function setCredential($credential){
    $this->credential = $credential;
    return $this;
  }
  
  protected function netVersion(){
    $cb = new Callback;
    $this->web3->net->version($cb);
    return $cb->result;
  }
  
	protected function getTransactionCount($address){
		$cb = new Callback;
    $this->web3->eth->getTransactionCount($address,'pending',$cb);
    return '0x' . $cb->result->toHex();
	}  
  
  protected function estimateGasPrice(){
    $cb = new Callback;
    $this->web3->eth->gasPrice($cb);
    return '0x' . $cb->result->toHex();
  }
  
  protected function estimateGasUsage($tx){
    //var_dump($tx);
    $cb = new Callback;
    $this->web3->eth->estimateGas($tx, $cb);
    return '0x' . $cb->result->toHex();
  }
  
  public function transact($tx): string{
    if(!isset($this->credential)){
      throw new Exception('credential not set');
    }
    
    $from = $this->credential->getAddress();
    
    $tx['from'] = $from;
    
    if(!isset($tx['nonce'])) {
      $tx['nonce'] = $this->getTransactionCount($from);    
    }
    
    if(!isset($tx['chainId'])){
      $tx['chainId'] = $this->netVersion();
    }
    
    if(!isset($tx['value'])) {
      if(isset($this->value)){
        $tx['value'] = $this->value;
      } else{
        $tx['value'] = '0x0';
      }
    }
    
    if(!isset($tx['gasPrice'])){
      if(isset($this->gasPrice)){
        $tx['gasPrice'] = $this->gasPrice;
      } else {
        $tx['gasPrice'] = $this->estimateGasPrice();
      }
    }
    
    if(!isset($tx['gasLimit'])){
      if(isset($this->gasLimit)){
        $tx['gasLimit'] = $this->gasLimit;
      } else{
        $tx['gasLimit'] = $this->estimateGasUsage($tx);
      }
    }
    
    $stx = $this->credential->signTransaction($tx);
    
    $cb = new Callback;
    $this->web3->eth->sendRawTransaction($stx,$cb);
        
    return $cb->result;
  }  
    
}