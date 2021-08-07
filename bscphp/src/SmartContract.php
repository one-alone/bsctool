<?php
namespace bsctool;

use Exception;
use Web3\Contract;
use Web3\Utils;

class SmartContract extends Contract{
  protected $web3;
  
  //context
  protected $gasPrice;
  protected $gasLimit;
  protected $value;
  protected $credential;
  
  protected $txMethods = [];
  protected $viewMethods = [];
  protected $eventSignatures = [];
    
  public function __construct($web3, $credential, $abi){
    parent::__construct($web3->provider, $abi);
    $this->web3 = $web3;
    $this->credential = $credential;
        
    foreach($this->events as $name => $event){
      $sig = $this->ethabi->encodeEventSignature($event);
      $this->eventSignatures[$sig] = $event;
    }
    foreach($this->functions as $function){
      if($function['stateMutability'] == 'view'){
        $this->viewMethods[] = $function['name'];
      }else{
        $this->txMethods[] = $function['name']; 
      }
    }
  }
  
  public function setGasPrice($price){
    if(!$price){
      unset($this->gasPrice);
    }else{
      $this->gasPrice = $price;
    }
    return $this;
  }
  
  public function setGasLimit($limit){
    if(!$limit) {
      unset($this->gasLimit);
    }else{
      $this->gasLimit = $limit;
    }
    return $this;
  }
  
  public function setValue($value){
    if(!$value){
      unset($this->value);
    }else{
      $this->value = $value;
    }
    return $this;
  }
  
  public function setCredential($credential){
    $this->credential = $credential;
    return $this;
  }
  
  protected function transact($tx): string{
    if(!isset($this->credential)){
      throw new \Exception('credential not set');
    }
    $transactor = new Transactor($this->web3, $this->credential);
    $tx['to'] = $this->getToAddress();
    return $transactor->transact($tx);
  }  
  
  protected function isTxMethod($name){
    return in_array($name, $this->txMethods);
  }
  
  protected function isViewMethod($name){
    return in_array($name, $this->viewMethods);
  }
  
  public function instantiate(){
    $args = func_get_args();
    
    $data = $this->getData(...$args);
    $tx = [
      'data' => '0x' . $data
    ];
    return $this->transact($tx);
  }
  
  public function __call($name, $args){
    if($this->isTxMethod($name)){
      $data = $this->getData($name, ...$args);
      $tx = [
        'data' => '0x' . $data
      ];
      return $this->transact($tx);
    }
    if($this->isViewMethod($name)){
      $cb = new Callback;
      $args[] = $cb;
      $this->call($name, ...$args);
      $values = array_values($cb->result);  
      return count($values) > 1 ? $values : $values[0];
    }
    throw new Exception('method not supported');
  }
  
  public function queryEvents($topics = [], $fromBlock = 'latest', $toBlock = 'latest'){
    if(is_numeric($fromBlock)) {
      $fromBlock = Utils::toHex($fromBlock, true);
    }
    if(is_numeric($toBlock)) {
      $toBlock = Utils::toHex($toBlock, true);
    }
    $filter = [
      'fromBlock' => $fromBlock,
      'toBlock' => $toBlock,
      'address' => $this->getToAddress(),
      'topics' => $topics
    ];
    $cb = new Callback;
    $this->web3->eth->getLogs($filter, $cb);
    $decodedEvents = [];
    foreach($cb->result as $log){
      if(!array_key_exists($log->topics[0], $this->eventSignatures)){
        $msg = sprintf("unknown event: %s, skip", $log->topics[0]);
        echo $msg . PHP_EOL; continue;
      }
      
      $eventAbi = $this->eventSignatures[$log->topics[0]];
      
      $types = [];
      $names = [];
      for($i = 0; $i < count($eventAbi['inputs']); $i++){
        $types[] = $eventAbi['inputs'][$i]['type'];
        $names[] = $eventAbi['inputs'][$i]['name'];
      }
      $params = '';
      for($i = 1;$i < count($log->topics);$i++){
        $params = $params . Utils::stripZero($log->topics[$i]);
      }
      $params = $params . Utils::stripZero($log->data);
      //echo $eventAbi['name'] . ' => ' . $params . PHP_EOL;
      $decodedParams = $this->ethabi->decodeParameters($types, $params);
      
      $decodedEvent = (object)[
        'blockHash' => $log->blockHash,
        'blockNumber' => $log->blockNumber,
        'transactionHash' => $log->transactionHash,
        'removed' => $log->removed,
        'address' => $log->address,
        'name' => $eventAbi['name'],
        'params' => []
      ];
      for($i = 0; $i < count($names); $i++){
        $decodedEvent->params[$names[$i]] = $decodedParams[$i];
      }
      $decodedEvents[] = $decodedEvent;
    }
    
    return $decodedEvents;
  }
  
}